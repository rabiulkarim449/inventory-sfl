<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use ME\SflInventory\Models\FgClosingNote;
use ME\SflInventory\Services\MerchandisingLink;

/**
 * Style Closing Summary (Finished Goods menu): the same sheet as the Manual
 * Challan one, filled by the system — nothing is typed or saved. One sheet per
 * PO number of an order; each style / colour line of that PO is a product block.
 *   BOM Qty  = PO qty (Merchandising order PO)
 *   Ship Qty = shipment lines linked to the PO (deleted shipments left out)
 *   Trims    = what is left in store of the items brought for the style (see trimsLeft()); the
 *              item's "Closing Summary Row" (item form) says which line it is counted on;
 *              Cutting Parts / Emb./Print Parts come from Merchandising production (see productionLeft())
 * Remarks (per style / colour line) and Comments are typed (Edit) and kept in inv_fg_closing_notes.
 */
class FgClosingSummaryController extends Controller
{
    private const PERM = 'inv_fg_closing_summary';

    public function index(Request $request): View
    {
        $this->authorize(self::PERM . '.list');

        $rows = $this->poGroups();
        if ($request->filled('search')) {
            $needle = mb_strtolower(trim($request->search));
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['po_no'] . ' ' . $r['order_no'] . ' ' . $r['buyer'] . ' ' . $r['styles']), $needle));
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $summaries = new LengthAwarePaginator($rows->forPage($page, 20)->values(), $rows->count(), 20, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);

        return view('sfl-inventory::admin.fg-closing-summaries.index', compact('summaries'));
    }

    public function show(int $orderPo): View
    {
        $this->authorize(self::PERM . '.view');

        return view('sfl-inventory::admin.fg-closing-summaries.show', ['summary' => $this->sheet($orderPo), 'orderPo' => $orderPo]);
    }

    public function print(int $orderPo): View
    {
        $this->authorize(self::PERM . '.print');

        return view('sfl-inventory::admin.fg-closing-summaries.print', ['summary' => $this->sheet($orderPo), 'hideGeneratedNote' => true]);
    }

    public function edit(int $orderPo): View
    {
        $this->authorize(self::PERM . '.edit');

        return view('sfl-inventory::admin.fg-closing-summaries.edit', ['summary' => $this->sheet($orderPo), 'orderPo' => $orderPo]);
    }

    /** Saves only the typed parts: Remarks per style / colour line and Comments. */
    public function update(Request $request, int $orderPo): RedirectResponse
    {
        $this->authorize(self::PERM . '.edit');
        $summary = $this->sheet($orderPo);
        $data = $request->validate([
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ], [], ['remarks.*' => 'Remarks', 'comments' => 'Comments']);

        // Only lines of this PO are kept.
        $lineIds = $summary->products->pluck('id')->map(fn ($id) => (string) $id)->all();
        $remarks = collect($data['remarks'] ?? [])->only($lineIds)->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->all();

        FgClosingNote::updateOrCreate(
            ['msfl_order_id' => $summary->order_id, 'po_no' => $summary->po_no],
            ['remarks' => $remarks ?: null, 'comments' => trim((string) ($data['comments'] ?? '')) ?: null, 'updated_by' => auth()->id()]
        );

        return redirect()->route('inventory.fg-closing-summaries.show', $orderPo)->with('success', 'Remarks and comments saved.');
    }

    /**
     * POs that have a Finished Goods receive or a shipment, grouped by order + PO number.
     * Each row: key (first order PO id), po_no, order_no, buyer, styles, bom, ship, last date.
     */
    private function poGroups(): Collection
    {
        if (! app(MerchandisingLink::class)->available()) {
            return collect();
        }

        $fg = DB::table('inv_finished_goods_receives')->whereNull('deleted_at')->whereNotNull('msfl_order_po_id')
            ->groupBy('msfl_order_po_id')->selectRaw('msfl_order_po_id po, MAX(receive_date) d')->pluck('d', 'po');
        $shipped = $this->shippedQty();
        $shipDates = DB::table('inv_shipment_items as si')->join('inv_shipments as s', 's.id', '=', 'si.shipment_id')
            ->whereNull('s.deleted_at')->whereNotNull('si.msfl_order_po_id')
            ->groupBy('si.msfl_order_po_id')->selectRaw('si.msfl_order_po_id po, MAX(s.shipment_date) d')->pluck('d', 'po');

        $ids = $fg->keys()->merge($shipDates->keys())->unique()->all();
        if ($ids === []) {
            return collect();
        }

        // Every line of the same order + PO number counts, not only the ones with a receive / shipment.
        $keys = DB::table('msfl_order_pos')->whereIn('id', $ids)->get(['order_id', 'po_no']);
        $lines = \ME\MerchandisingSfl\Models\OrderPo::query()
            ->with(['order:id,order_no,buyer_id', 'order.buyer:id,name', 'style:id,style_no,name'])
            ->where(fn ($q) => $keys->each(fn ($k) => $q->orWhere(fn ($w) => $w->where('order_id', $k->order_id)->where('po_no', $k->po_no))))
            ->orderBy('id')->get();

        return $lines->groupBy(fn ($l) => $l->order_id . '|' . $l->po_no)->map(function (Collection $group) use ($fg, $shipDates, $shipped) {
            $first = $group->first();
            $date = $group->map(fn ($l) => max((string) ($fg[$l->id] ?? ''), (string) ($shipDates[$l->id] ?? '')))->max();

            return [
                'key' => $first->id,
                'po_no' => $first->po_no,
                'order_no' => $first->order->order_no ?? '',
                'buyer' => $first->order->buyer->name ?? '',
                'styles' => $group->map(fn ($l) => $l->style->style_no ?? '')->filter()->unique()->implode(', '),
                'bom' => (float) $group->sum('po_qty'),
                'ship' => (float) $group->sum(fn ($l) => $shipped[$l->id] ?? 0),
                'date' => $date ?: null,
            ];
        })->sortByDesc('date')->values();
    }

    /** The sheet of one order + PO number, shaped like the Manual Challan summary for the sheet view. */
    private function sheet(int $orderPoId): object
    {
        abort_unless(app(MerchandisingLink::class)->available(), 404);

        $po = \ME\MerchandisingSfl\Models\OrderPo::query()->findOrFail($orderPoId);
        $lines = \ME\MerchandisingSfl\Models\OrderPo::query()
            ->with(['style:id,style_no,name', 'color:id,name'])
            ->where('order_id', $po->order_id)->where('po_no', $po->po_no)
            ->orderBy('id')->get();
        $shipped = $this->shippedQty($lines->pluck('id')->all());
        $showColor = $lines->count() > 1;
        $trims = $this->trimsLeft($lines);
        $note = FgClosingNote::query()->where('msfl_order_id', $po->order_id)->where('po_no', $po->po_no)->first();

        $products = $lines->map(fn ($l) => (object) [
            'id' => $l->id,
            'product' => implode("\n", array_filter([
                $l->style->name ?? null,
                isset($l->style->style_no) ? 'Style: ' . $l->style->style_no : null,
                $showColor && isset($l->color->name) ? 'Color: ' . $l->color->name : null,
                'PO: ' . $l->po_no,
            ])),
            'bom_qty' => (float) $l->po_qty,
            'ship_qty' => (float) ($shipped[$l->id] ?? 0),
            'remarks' => $note->remarks[$l->id] ?? null,
            'trims' => array_merge($trims[$l->id] ?? [], $this->productionLeft($l)),
        ]);

        return (object) [
            'factory_name' => config('sfl-inventory.company.name'),
            'factory_location' => config('sfl-inventory.company.address'),
            'order_id' => $po->order_id,
            'po_no' => $po->po_no,
            'report_date' => today(),
            'products' => $products,
            'comments' => $note?->comments,
        ];
    }

    /**
     * Trims left per order PO line: [line id => [trim key => "15 Pcs"]].
     * Store entries count for a line when they carry its style and either its PO or no PO
     * (as Merchandising's MRP report counts them):
     *   received (posted GRNs; style / PO from the GRN, else its Store Order's purchase requisition)
     *   − issued (approved store issues) + returned (issue returns)
     * Only items received for the style are shown — an item issued from general stock has no
     * receipt of its own here, so no "left" can be worked out for it. Totals are kept per unit.
     */
    private function trimsLeft(Collection $lines): array
    {
        $styleIds = $lines->pluck('style_id')->unique()->values()->all();
        $rowItems = DB::table('inv_items as i')->leftJoin('inv_units as u', 'u.id', '=', 'i.unit_id')
            ->whereNotNull('i.closing_summary_row')->get(['i.id', 'i.closing_summary_row as row_key', 'u.short_name as unit'])->keyBy('id');
        if ($styleIds === [] || $rowItems->isEmpty()) {
            return [];
        }
        $itemIds = $rowItems->keys()->all();

        $prLinked = Schema::hasColumn('inv_purchase_requisitions', 'msfl_style_id');
        $grn = DB::table('inv_grn_items as gi')->join('inv_grns as g', 'g.id', '=', 'gi.grn_id')
            ->where('g.status', 'posted')->whereNull('g.deleted_at')->whereIn('gi.item_id', $itemIds);
        if ($prLinked) {
            $grn->leftJoin('inv_purchase_orders as po', 'po.id', '=', 'g.purchase_order_id')
                ->leftJoin('inv_purchase_requisitions as pr', 'pr.id', '=', 'po.purchase_requisition_id')
                ->where(fn ($q) => $q->whereIn('g.msfl_style_id', $styleIds)->orWhere(fn ($q) => $q->whereNull('g.msfl_style_id')->whereIn('pr.msfl_style_id', $styleIds)))
                ->select(DB::raw('COALESCE(g.msfl_style_id, pr.msfl_style_id) as style_id'), DB::raw('COALESCE(g.msfl_order_po_id, pr.msfl_order_po_id) as po_id'));
        } else {
            $grn->whereIn('g.msfl_style_id', $styleIds)->select('g.msfl_style_id as style_id', 'g.msfl_order_po_id as po_id');
        }
        $received = $grn->addSelect('gi.item_id', DB::raw('gi.received_qty as qty'))->get();

        $issued = DB::table('inv_issue_items as ii')->join('inv_issues as s', 's.id', '=', 'ii.issue_id')
            ->where('s.status', 'approved')->whereNull('s.deleted_at')
            ->whereIn('s.msfl_style_id', $styleIds)->whereIn('ii.item_id', $itemIds)
            ->get(['s.msfl_style_id as style_id', 's.msfl_order_po_id as po_id', 'ii.item_id', 'ii.issued_qty as qty']);

        $returned = DB::table('inv_issue_return_items as ri')->join('inv_issue_returns as r', 'r.id', '=', 'ri.return_id')
            ->join('inv_issues as s', 's.id', '=', 'r.issue_id')
            ->whereNull('r.deleted_at')->whereNull('s.deleted_at')
            ->whereIn('s.msfl_style_id', $styleIds)->whereIn('ri.item_id', $itemIds)
            ->get(['s.msfl_style_id as style_id', 's.msfl_order_po_id as po_id', 'ri.item_id', 'ri.returned_qty as qty']);

        $out = [];
        foreach ($lines as $line) {
            $mine = fn ($x) => (int) $x->style_id === (int) $line->style_id && ($x->po_id === null || (int) $x->po_id === (int) $line->id);
            $in = $received->filter($mine)->groupBy('item_id')->map(fn ($g) => $g->sum(fn ($x) => (float) $x->qty));
            $issuedBy = $issued->filter($mine)->groupBy('item_id')->map(fn ($g) => $g->sum(fn ($x) => (float) $x->qty));
            $returnedBy = $returned->filter($mine)->groupBy('item_id')->map(fn ($g) => $g->sum(fn ($x) => (float) $x->qty));

            $byRow = [];
            foreach ($in as $itemId => $qty) {
                $item = $rowItems[$itemId];
                $unit = (string) ($item->unit ?? '');
                $left = $qty - ($issuedBy[$itemId] ?? 0) + ($returnedBy[$itemId] ?? 0);
                $byRow[$item->row_key][$unit] = ($byRow[$item->row_key][$unit] ?? 0) + $left;
            }
            $out[$line->id] = collect($byRow)->map(fn ($units) => collect($units)
                ->map(fn ($q, $unit) => trim((fmod(round($q, 2), 1) == 0 ? number_format($q) : number_format($q, 2)) . ' ' . $unit))
                ->implode(', '))->all();
        }

        return $out;
    }

    /**
     * Cut pieces left over, from Merchandising production (Production → Cutting / Embroidery):
     *   Cutting Parts   = cut − sent to sewing − still away at embroidery (ProductionFlow::cuttingBalance)
     *   Emb./Print Parts = per part, came back from embroidery − sent to sewing
     * Blank when the PO has no cutting entry, or (embroidery) doesn't go to embroidery.
     */
    private function productionLeft(\ME\MerchandisingSfl\Models\OrderPo $line): array
    {
        if (! class_exists(\ME\MerchandisingSfl\Services\ProductionFlow::class)) {
            return [];
        }
        $flow = app(\ME\MerchandisingSfl\Services\ProductionFlow::class);
        $sum = $flow->summary($line);
        if ((int) ($sum['cutting']['pass'] ?? 0) <= 0) {
            return [];
        }

        $out = ['cutting_parts' => number_format($flow->cuttingBalance($sum)) . ' PCS'];
        $parts = $sum['embroidery']['parts'] ?? [];
        if ($parts !== []) {
            $sewn = (int) ($sum['sewing']['input'] ?? 0);
            $left = collect($parts)->map(fn ($r) => max(0, (int) $r['pass'] - $sewn));
            $out['emb_print_parts'] = count($left) === 1
                ? number_format($left->first()) . ' PCS'
                : $left->map(fn ($q, $part) => $part . ' ' . number_format($q))->implode(', ');
        }

        return $out;
    }

    /** Shipped qty per order PO id (deleted shipments left out), as Merchandising's Order Closing report counts it. */
    private function shippedQty(?array $poIds = null): Collection
    {
        return DB::table('inv_shipment_items as si')->join('inv_shipments as s', 's.id', '=', 'si.shipment_id')
            ->whereNull('s.deleted_at')->whereNotNull('si.msfl_order_po_id')
            ->when($poIds !== null, fn ($q) => $q->whereIn('si.msfl_order_po_id', $poIds))
            ->groupBy('si.msfl_order_po_id')->selectRaw('si.msfl_order_po_id po, SUM(si.quantity) q')->pluck('q', 'po');
    }
}
