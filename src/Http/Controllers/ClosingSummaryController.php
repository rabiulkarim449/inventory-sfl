<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\SflInventory\Models\ClosingSummary;
use ME\SflInventory\Support\ClosingSummaryTrims;

/**
 * Style Closing Summary (Manual Challan section). Typed by hand: no number and
 * no approval, so it can be edited any time. Uses the Manual Challan permissions.
 */
class ClosingSummaryController extends Controller
{
    private const PERM = 'inv_manual_challan';

    public function index(Request $request): View
    {
        $this->authorize(self::PERM . '.list');

        $summaries = ClosingSummary::query()->with(['products:id,closing_summary_id,product,bom_qty,ship_qty', 'creator:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $like = '%' . $request->search . '%';
                $q->where(fn ($q) => $q->where('po_no', 'like', $like)->orWhereHas('products', fn ($p) => $p->where('product', 'like', $like)));
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('report_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('report_date', '<=', $request->date_to))
            ->orderByDesc('report_date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('sfl-inventory::admin.closing-summaries.index', compact('summaries'));
    }

    public function create(): View
    {
        $this->authorize(self::PERM . '.add');

        return view('sfl-inventory::admin.closing-summaries.form', [
            'summary' => new ClosingSummary(['report_date' => today()]),
            'earlierLogos' => ClosingSummary::earlierLogos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize(self::PERM . '.add');
        $data = $this->validated($request);

        $summary = DB::transaction(function () use ($data, $request) {
            $summary = ClosingSummary::create([
                'report_date' => $data['report_date'],
                'po_no' => $data['po_no'] ?? null,
                'logo' => $this->logoPath($request, null),
                'comments' => $data['comments'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $this->saveProducts($summary, $data['products']);

            return $summary;
        });

        return redirect()->route('inventory.closing-summaries.show', $summary)->with('success', 'Closing Summary saved.');
    }

    public function show(ClosingSummary $closingSummary): View
    {
        $this->authorize(self::PERM . '.view');

        return view('sfl-inventory::admin.closing-summaries.show', ['summary' => $closingSummary->load(['products', 'creator'])]);
    }

    public function edit(ClosingSummary $closingSummary): View
    {
        $this->authorize(self::PERM . '.edit');

        return view('sfl-inventory::admin.closing-summaries.form', [
            'summary' => $closingSummary->load('products'),
            'earlierLogos' => ClosingSummary::earlierLogos(),
        ]);
    }

    public function update(Request $request, ClosingSummary $closingSummary): RedirectResponse
    {
        $this->authorize(self::PERM . '.edit');
        $data = $this->validated($request);

        DB::transaction(function () use ($closingSummary, $data, $request) {
            $closingSummary->update([
                'report_date' => $data['report_date'],
                'po_no' => $data['po_no'] ?? null,
                'logo' => $this->logoPath($request, $closingSummary->logo),
                'comments' => $data['comments'] ?? null,
            ]);
            $this->saveProducts($closingSummary, $data['products']);
        });

        return redirect()->route('inventory.closing-summaries.show', $closingSummary)->with('success', 'Closing Summary updated.');
    }

    public function destroy(ClosingSummary $closingSummary): RedirectResponse
    {
        $this->authorize(self::PERM . '.delete');
        $closingSummary->delete();

        return redirect()->route('inventory.closing-summaries.index')->with('success', 'Closing Summary deleted.');
    }

    public function print(ClosingSummary $closingSummary): View
    {
        $this->authorize(self::PERM . '.print');

        return view('sfl-inventory::admin.closing-summaries.print', ['summary' => $closingSummary->load('products'), 'hideGeneratedNote' => true]);
    }

    /**
     * The logo to keep: a new upload wins, then one picked from earlier summaries,
     * then the current one — unless "No logo" was chosen.
     */
    private function logoPath(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('logo_file')) {
            return $request->file('logo_file')->store(ClosingSummary::LOGO_DIR, 'public');
        }
        $pick = (string) $request->input('logo_pick', '');
        if ($pick === 'none') {
            return null;
        }
        // Only a file from the logo folder can be picked.
        if ($pick !== '' && $pick !== 'keep' && str_starts_with($pick, ClosingSummary::LOGO_DIR . '/') && ! str_contains($pick, '..')
            && Storage::disk('public')->exists($pick)) {
            return $pick;
        }

        return $current;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'report_date' => ['required', 'date'],
            'po_no' => ['nullable', 'string', 'max:100'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'logo_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'logo_pick' => ['nullable', 'string', 'max:255'],
            'products' => ['required', 'array'],
            'products.*.product' => ['nullable', 'string', 'max:1000'],
            'products.*.bom_qty' => ['nullable', 'numeric', 'min:0'],
            'products.*.ship_qty' => ['nullable', 'numeric', 'min:0'],
            'products.*.remarks' => ['nullable', 'string', 'max:2000'],
            'products.*.trims' => ['nullable', 'array'],
            'products.*.trims.*' => ['nullable', 'string', 'max:50'],
        ], [], [
            'report_date' => 'Report Date', 'logo_file' => 'Logo', 'products.*.product' => 'Product',
            'products.*.bom_qty' => 'BOM Qty', 'products.*.ship_qty' => 'Ship Qty', 'products.*.trims.*' => 'Trims value',
        ]);

        $keys = array_keys(ClosingSummaryTrims::all());
        $data['products'] = collect($data['products'])->map(function ($p) use ($keys) {
            $p['trims'] = collect($p['trims'] ?? [])->only($keys)->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->all() ?: null;

            return $p;
        })->filter(fn ($p) => filled($p['product'] ?? null) || filled($p['bom_qty'] ?? null) || filled($p['ship_qty'] ?? null)
            || filled($p['remarks'] ?? null) || ! empty($p['trims']))->values()->all();

        if ($data['products'] === []) {
            throw ValidationException::withMessages(['products' => 'Fill in at least one product.']);
        }

        return $data;
    }

    private function saveProducts(ClosingSummary $summary, array $products): void
    {
        $summary->products()->delete();
        foreach ($products as $i => $p) {
            $summary->products()->create([
                'sequence' => $i + 1,
                'product' => $p['product'] ?? null,
                'bom_qty' => filled($p['bom_qty'] ?? null) ? $p['bom_qty'] : null,
                'ship_qty' => filled($p['ship_qty'] ?? null) ? $p['ship_qty'] : null,
                'remarks' => $p['remarks'] ?? null,
                'trims' => $p['trims'],
            ]);
        }
    }
}
