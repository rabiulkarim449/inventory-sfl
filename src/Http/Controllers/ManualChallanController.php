<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Models\ManualChallan;
use ME\SflInventory\Support\ManualChallanTypes;

/**
 * Manual Delivery Challan — one controller for every type ({type} in the URL).
 * Submit → Pending (Challan No given now, for good) → Approved / Rejected.
 * Only a pending challan can be edited or deleted; printing works in any status.
 */
class ManualChallanController extends Controller
{
    private const PERM = 'inv_manual_challan';

    /** Key on the central Approvals page and on Settings > Mail Notifications. */
    public const APPROVAL_MODULE = 'inventory.manual_challan';

    public function index(Request $request, string $type): View
    {
        $this->authorize(self::PERM . '.list');

        $challans = ManualChallan::query()->where('type', $type)
            ->with(['items:id,manual_challan_id,qty', 'creator:id,name'])
            ->withCount('items')
            ->when($request->filled('search'), function ($q) use ($request) {
                $like = '%' . $request->search . '%';
                $q->where(fn ($q) => $q->where('challan_no', 'like', $like)->orWhere('to_name', 'like', $like)
                    ->orWhereHas('items', fn ($i) => $i->where('po_no', 'like', $like)->orWhere('style', 'like', $like)->orWhere('description', 'like', $like)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('challan_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('challan_date', '<=', $request->date_to))
            // Same date: the one entered first comes first (created_at keeps the time).
            ->orderByDesc('challan_date')->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('sfl-inventory::admin.manual-challans.index', ['type' => $type, 'info' => ManualChallanTypes::get($type), 'challans' => $challans]);
    }

    public function create(string $type): View
    {
        $this->authorize(self::PERM . '.add');

        $challan = new ManualChallan(['type' => $type, 'challan_date' => today(), 'optional_columns' => []]);

        return view('sfl-inventory::admin.manual-challans.form', $this->formData($type, $challan));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->authorize(self::PERM . '.add');
        $data = $this->validated($request, $type, true);
        $date = Carbon::parse($data['challan_date']);

        // Two people submitting at the same moment: the unique Challan No
        // makes one of them take the next number instead.
        for ($attempt = 1; ; $attempt++) {
            try {
                $challan = DB::transaction(function () use ($data, $type, $date) {
                    $challan = ManualChallan::create([
                        'type' => $type,
                        'challan_no' => ManualChallan::nextNumber($date),
                        'challan_date' => $date->toDateString(),
                        'to_name' => $data['to_name'],
                        'to_address' => $data['to_address'] ?? null,
                        'to_contact' => $data['to_contact'] ?? null,
                        'optional_columns' => $data['optional_columns'],
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                    ]);
                    $this->saveItems($challan, $data['items']);

                    return $challan;
                });
                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 5) {
                    throw $e;
                }
            }
        }

        $this->requestApproval($challan);

        return redirect()->route('inventory.manual-challans.show', [$type, $challan])
            ->with('success', "Challan {$challan->challan_no} submitted — waiting for approval.");
    }

    public function show(string $type, int $challan): View
    {
        $this->authorize(self::PERM . '.view');
        $challan = $this->find($type, $challan)->load(['items', 'creator', 'approver']);

        return view('sfl-inventory::admin.manual-challans.show', ['type' => $type, 'info' => ManualChallanTypes::get($type), 'challan' => $challan]);
    }

    public function edit(string $type, int $challan): View|RedirectResponse
    {
        $this->authorize(self::PERM . '.edit');
        $challan = $this->find($type, $challan)->load('items');
        if (! $challan->isPending()) {
            return $this->locked($type, $challan);
        }

        return view('sfl-inventory::admin.manual-challans.form', $this->formData($type, $challan));
    }

    public function update(Request $request, string $type, int $challan): RedirectResponse
    {
        $this->authorize(self::PERM . '.edit');
        $challan = $this->find($type, $challan);
        if (! $challan->isPending()) {
            return $this->locked($type, $challan);
        }
        $data = $this->validated($request, $type, false);

        DB::transaction(function () use ($challan, $data) {
            // Challan No and Date stay as submitted.
            $challan->update([
                'to_name' => $data['to_name'],
                'to_address' => $data['to_address'] ?? null,
                'to_contact' => $data['to_contact'] ?? null,
                'optional_columns' => $data['optional_columns'],
            ]);
            $this->saveItems($challan, $data['items']);
        });

        return redirect()->route('inventory.manual-challans.show', [$type, $challan])->with('success', 'Challan updated.');
    }

    public function destroy(string $type, int $challan): RedirectResponse
    {
        $this->authorize(self::PERM . '.delete');
        $challan = $this->find($type, $challan);
        if (! $challan->isPending()) {
            return $this->locked($type, $challan);
        }
        // Soft delete: the Challan No stays taken. Its request leaves the Approvals list.
        $challan->delete();
        $this->centralApprovals($challan)?->delete();

        return redirect()->route('inventory.manual-challans.index', $type)->with('success', "Challan {$challan->challan_no} deleted.");
    }

    public function approve(Request $request, string $type, int $challan): RedirectResponse
    {
        return $this->decide($request, $type, $challan, 'approved');
    }

    public function reject(Request $request, string $type, int $challan): RedirectResponse
    {
        return $this->decide($request, $type, $challan, 'rejected');
    }

    /** The printed challan — any status; the Approved By signature appears once approved. */
    public function print(string $type, int $challan): View
    {
        $this->authorize(self::PERM . '.print');
        $challan = $this->find($type, $challan)->load(['items', 'creator', 'approver']);

        // A delivery challan is signed, so the layout's "no signature required" note is left off.
        return view('sfl-inventory::admin.manual-challans.print', ['type' => $type, 'info' => ManualChallanTypes::get($type), 'challan' => $challan, 'hideGeneratedNote' => true]);
    }

    private function decide(Request $request, string $type, int $id, string $status): RedirectResponse
    {
        $this->authorize(self::PERM . '.approve');
        $data = $request->validate(['approval_remarks' => ['nullable', 'string', 'max:255']]);
        $challan = $this->find($type, $id);
        if (! $challan->isPending()) {
            return $this->locked($type, $challan);
        }
        $challan->update([
            'status' => $status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_remarks' => $data['approval_remarks'] ?? null,
        ]);
        // Decided here: close its request on the central Approvals page too. Updated
        // directly, not through ApprovalService, as the challan is already settled.
        $this->centralApprovals($challan)?->update([
            'status' => $status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $data['approval_remarks'] ?? null,
        ]);

        return redirect()->route('inventory.manual-challans.show', [$type, $challan])
            ->with('success', "Challan {$challan->challan_no} " . ($status === 'approved' ? 'approved.' : 'rejected.'));
    }

    /**
     * Puts the challan on the central Approvals page; that also emails the people picked on
     * Settings > Mail Notifications > "Approval: Manual Challan". Without the host's approval
     * system nothing happens and the challan is approved on its own page only.
     */
    private function requestApproval(ManualChallan $challan): void
    {
        if (! class_exists(\App\Services\ApprovalService::class)) {
            return;
        }
        $info = $challan->typeInfo();
        app(\App\Services\ApprovalService::class)->request([
            'module' => self::APPROVAL_MODULE,
            'approvable' => $challan,
            'title' => "Manual Challan - {$challan->challan_no} ({$info['menu']})",
            'description' => auth()->user()?->name . ' submitted a ' . $info['title'] . ' to ' . $challan->to_name
                . ' — ' . inv_qty($challan->load('items')->totalQty()) . ' pcs.',
            'route_name' => 'inventory.manual-challans.show',
            'route_params' => ['type' => $challan->type, 'challan' => $challan->id],
            'requested_by' => auth()->id(),
        ]);
    }

    /** The challan's still-pending request on the central Approvals page (query), if that system exists. */
    private function centralApprovals(ManualChallan $challan)
    {
        if (! class_exists(\App\Models\Approval::class)) {
            return null;
        }

        return \App\Models\Approval::query()->where('module', self::APPROVAL_MODULE)
            ->where('approvable_type', ManualChallan::class)->where('approvable_id', $challan->id)->where('status', 'pending');
    }

    private function find(string $type, int $id): ManualChallan
    {
        return ManualChallan::query()->where('type', $type)->findOrFail($id);
    }

    private function locked(string $type, ManualChallan $challan): RedirectResponse
    {
        return redirect()->route('inventory.manual-challans.show', [$type, $challan])
            ->with('error', 'This challan is ' . strtolower(ManualChallan::STATUSES[$challan->status][0]) . ' — it can no longer be changed.');
    }

    private function formData(string $type, ManualChallan $challan): array
    {
        return ['type' => $type, 'info' => ManualChallanTypes::get($type), 'challan' => $challan, 'sizes' => ManualChallanTypes::get($type)['sizes'] ?? []];
    }

    /** Validated header + lines; blank lines are dropped, at least one must remain. */
    private function validated(Request $request, string $type, bool $creating): array
    {
        $allowed = ManualChallanTypes::optionalColumns($type);
        $data = $request->validate([
            'challan_date' => [$creating ? 'required' : 'nullable', 'date'],
            'to_name' => ['required', 'string', 'max:255'],
            'to_address' => ['nullable', 'string', 'max:255'],
            'to_contact' => ['nullable', 'string', 'max:100'],
            'optional_columns' => ['nullable', 'array'],
            'optional_columns.*' => ['string'],
            'items' => ['required', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.po_no' => ['nullable', 'string', 'max:100'],
            'items.*.style' => ['nullable', 'string', 'max:100'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.color' => ['nullable', 'string', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:50'],
            'items.*.sizes' => ['nullable', 'array'],
            'items.*.sizes.*' => ['nullable', 'numeric', 'min:0'],
            'items.*.buyer_sample_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.sfl_sample_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.hanger_carton' => ['nullable', 'string', 'max:50'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ], [], ['to_name' => 'To', 'challan_date' => 'Date']);

        // Ticked green columns, kept in the type's own order; anything typed in an unticked one is dropped.
        $on = array_values(array_intersect($allowed, $data['optional_columns'] ?? []));
        $data['optional_columns'] = $on;
        $data['items'] = collect($data['items'])->map(function ($line) use ($on) {
            $sizes = collect($line['sizes'] ?? [])->only(array_intersect(ManualChallanTypes::SIZES, $on))
                ->map(fn ($v) => (float) $v)->filter(fn ($v) => $v > 0)->all();
            $line['sizes'] = $sizes ?: null;
            // Size-wise qty given: the total is their sum.
            $line['qty'] = $sizes ? array_sum($sizes) : (float) ($line['qty'] ?? 0);
            if (! in_array('hanger_carton', $on, true)) {
                $line['hanger_carton'] = null;
            }

            return $line;
        })->filter(fn ($line) => $line['qty'] > 0 || collect($line)->except(['qty', 'sizes', 'unit'])->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values()->all();

        if ($data['items'] === []) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Add at least one line.']);
        }

        return $data;
    }

    private function saveItems(ManualChallan $challan, array $lines): void
    {
        $challan->items()->delete();
        foreach ($lines as $i => $line) {
            $challan->items()->create([
                'sequence' => $i + 1,
                'description' => $line['description'] ?? null,
                'po_no' => $line['po_no'] ?? null,
                'style' => $line['style'] ?? null,
                'item_code' => $line['item_code'] ?? null,
                'color' => $line['color'] ?? null,
                'size' => $line['size'] ?? null,
                'sizes' => $line['sizes'],
                'buyer_sample_qty' => $line['buyer_sample_qty'] ?? null,
                'sfl_sample_qty' => $line['sfl_sample_qty'] ?? null,
                'qty' => $line['qty'],
                'unit' => ($line['unit'] ?? null) ?: 'PCS',
                'hanger_carton' => $line['hanger_carton'] ?? null,
                'remarks' => $line['remarks'] ?? null,
            ]);
        }
    }
}
