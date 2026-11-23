@extends(adminTheme() . 'layouts.app')

@php
    $editing = $challan->exists;
    // Ticked green columns (old input after a failed submit wins).
    $on = array_values(array_intersect(\ME\SflInventory\Support\ManualChallanTypes::optionalColumns($type), (array) old('optional_columns', $challan->optional_columns ?? [])));
    $lines = old('items', $editing ? $challan->items->map(fn ($i) => $i->only(['description', 'po_no', 'style', 'item_code', 'color', 'size', 'sizes', 'buyer_sample_qty', 'sfl_sample_qty', 'qty', 'unit', 'hanger', 'carton', 'remarks']))->all() : []);
    if (empty($lines)) {
        $lines = [[], []];
    }
    $pageTitle = ($editing ? 'Edit ' : 'New ') . $info['title'];
    $greenHeader = function (string $key, string $label) use ($on) {
        $id = 'mcCol_' . $key;
        return '<label class="mb-0 d-inline-flex align-items-center" for="' . $id . '" style="cursor:pointer;gap:4px">'
            . '<input type="checkbox" class="m-0" name="optional_columns[]" value="' . e($key) . '" id="' . $id . '" data-col-toggle="' . e($key) . '"' . (in_array($key, $on, true) ? ' checked' : '') . '>'
            . '<span>' . e($label) . '</span>'
            . '</label>';
    };
@endphp

@section('title')
    <title>{{ websiteTitle($pageTitle) }}</title>
@endsection

@push('css')
<style>
    /* Field colours as on the client's sheet: blue = automatic, yellow = typed, green = optional. */
    .mc-auto { background: #dbeafe !important; }
    .mc-optional { background: #dcfce7 !important; }
    .mc-lines th { white-space: nowrap; font-size: 12px; vertical-align: middle; text-align: center; }
    .mc-lines td { padding: 2px; vertical-align: middle; }
    .mc-lines input[type=text], .mc-lines input[type=number] { min-width: 60px; }
    /* Every box fills its whole column, whatever the header width. */
    .mc-lines td > input { width: 100% !important; }
    .mc-lines .mc-size input { min-width: 52px; text-align: right; }
    .mc-lines .mc-qty input { min-width: 70px; text-align: right; }
    /* An unticked green column: grey, empty and not writable. */
    .mc-lines input[data-col][readonly] { background: #f1f5f9; cursor: not-allowed; }
    .mc-lines input[data-qty][readonly] { background: #f8fafc; }
    /* Number boxes without the up / down arrows (digits only — see the script below). */
    .mc-lines input[type=number] { -moz-appearance: textfield; appearance: textfield; }
    .mc-lines input[type=number]::-webkit-outer-spin-button,
    .mc-lines input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>
@endpush

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <a href="{{ route('inventory.manual-challans.index', $type) }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger small"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ $editing ? route('inventory.manual-challans.update', [$type, $challan]) : route('inventory.manual-challans.store', $type) }}" id="mcForm">
                @csrf
                @if($editing) @method('PUT') @endif

                {{-- As on the paper challan: who it goes to on the left, Challan No and Date on the right. --}}
                <div class="row">
                    <div class="col-lg-7">
                        <div class="form-group row mb-2">
                            <label class="col-sm-3 col-form-label col-form-label-sm">To <span class="text-danger">*</span></label>
                            <div class="col-sm-9"><input type="text" name="to_name" class="form-control form-control-sm" value="{{ old('to_name', $challan->to_name) }}" required maxlength="255"></div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-3 col-form-label col-form-label-sm">Address</label>
                            <div class="col-sm-9"><input type="text" name="to_address" class="form-control form-control-sm" value="{{ old('to_address', $challan->to_address) }}" maxlength="255"></div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-3 col-form-label col-form-label-sm">Contact No</label>
                            <div class="col-sm-9"><input type="text" name="to_contact" class="form-control form-control-sm" value="{{ old('to_contact', $challan->to_contact) }}" maxlength="100"></div>
                        </div>
                    </div>
                    <div class="col-lg-4 offset-lg-1">
                        <div class="form-group row mb-2">
                            <label class="col-sm-4 col-form-label col-form-label-sm">Challan No</label>
                            <div class="col-sm-8"><input type="text" class="form-control form-control-sm mc-auto" value="{{ $editing ? $challan->challan_no : 'Given on Submit' }}" readonly></div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-4 col-form-label col-form-label-sm">Date <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                @if($editing)
                                    <input type="text" class="form-control form-control-sm mc-auto" value="{{ $challan->challan_date->format('d M Y') }}" readonly>
                                    <div class="form-text small">Fixed with the Challan No.</div>
                                @else
                                    {{-- Not blue: the user can change it, so it is not an automatic field. --}}
                                    <input type="date" name="challan_date" class="form-control form-control-sm" value="{{ old('challan_date', $challan->challan_date?->format('Y-m-d')) }}" required>
                                @endif
                            </div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-4 col-form-label col-form-label-sm">Vehicle No</label>
                            <div class="col-sm-8"><input type="text" name="vehicle_no" class="form-control form-control-sm" value="{{ old('vehicle_no', $challan->vehicle_no) }}" maxlength="100"></div>
                        </div>
                    </div>
                </div>

                @include('sfl-inventory::admin.manual-challans.partials.' . $type . '-form-table')
                <button type="button" class="btn btn-sm ui-act ui-act-create" id="mcAddRow" title="Add Line"><i class="fa-solid fa-plus"></i> Add Line</button>

                {{-- Printed under the Receiver's Signature; left blank, the receiver writes them by hand. --}}
                <div class="row mt-3">
                    <div class="col-lg-7">
                        <div class="form-group row mb-2">
                            <label class="col-sm-3 col-form-label col-form-label-sm">Receiver Name</label>
                            <div class="col-sm-9"><input type="text" name="receiver_name" class="form-control form-control-sm" value="{{ old('receiver_name', $challan->receiver_name) }}" maxlength="255"></div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-3 col-form-label col-form-label-sm">Receiver Contact</label>
                            <div class="col-sm-9"><input type="text" name="receiver_contact" class="form-control form-control-sm" value="{{ old('receiver_contact', $challan->receiver_contact) }}" maxlength="100"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-sm ui-act ui-act-save" title="{{ $editing ? 'Update' : 'Submit' }}"><i class="fa-solid fa-floppy-disk"></i> {{ $editing ? 'Update' : 'Submit for Approval' }}</button>
                    <a href="{{ route('inventory.manual-challans.index', $type) }}" class="btn btn-sm ui-act ui-act-cancel" title="Cancel"><i class="fa-solid fa-ban"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    const body = document.getElementById('mcRows');
    const sizes = @json($sizes);
    let next = 100000;

    function num(v) { const n = parseFloat(v); return isNaN(n) ? 0 : n; }
    function fmt(n) { return n ? (Math.round(n * 100) / 100).toString() : ''; }
    function ticked(col) { const box = document.querySelector('[data-col-toggle="' + col + '"]'); return !!(box && box.checked); }

    // Ticked column: writable. Unticked: emptied and read-only.
    function applyColumn(col) {
        const on = ticked(col);
        body.querySelectorAll('[data-col="' + col + '"]').forEach(function (input) {
            input.readOnly = !on;
            input.tabIndex = on ? 0 : -1;
            if (!on) { input.value = ''; }
        });
    }

    function recalc() {
        const sizeTotals = {};
        let grand = 0;
        body.querySelectorAll('tr').forEach(function (row, i) {
            row.querySelector('[data-sl]').textContent = i + 1;
            const qty = row.querySelector('[data-qty]');
            let sum = 0;
            sizes.forEach(function (s) {
                const v = num(row.querySelector('[data-size="' + s + '"]').value);
                sum += v;
                sizeTotals[s] = (sizeTotals[s] || 0) + v;
            });
            // Size-wise qty typed: Total Qty is their sum.
            qty.readOnly = sum > 0;
            if (sum > 0) { qty.value = fmt(sum); }
            // Part quantities (PP Sample: buyer's + SFL's): Total Qty is always their sum.
            const parts = row.querySelectorAll('[data-part]');
            if (parts.length) {
                let p = 0;
                parts.forEach(function (i) { p += num(i.value); });
                qty.readOnly = true;
                qty.value = fmt(p);
            }
            grand += num(qty.value);
        });
        sizes.forEach(function (s) {
            const cell = document.querySelector('[data-size-total="' + s + '"]');
            if (cell) { cell.textContent = fmt(sizeTotals[s] || 0); }
        });
        document.getElementById('mcGrandTotal').textContent = fmt(grand);
    }

    function applyAll() {
        document.querySelectorAll('[data-col-toggle]').forEach(function (box) { applyColumn(box.dataset.colToggle); });
        recalc();
    }

    document.getElementById('mcAddRow').addEventListener('click', function () {
        body.insertAdjacentHTML('beforeend', document.getElementById('mcRowTemplate').innerHTML.replace(/__INDEX__/g, next++));
        applyAll();
    });
    body.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove]');
        if (!btn) { return; }
        if (body.querySelectorAll('tr').length > 1) {
            btn.closest('tr').remove();
        } else {
            btn.closest('tr').querySelectorAll('input').forEach(function (i) { i.value = i.dataset.default || ''; });
        }
        recalc();
    });
    body.addEventListener('input', recalc);
    // Number boxes take digits and one decimal point only: no e, +, - and no change on mouse wheel.
    body.addEventListener('keydown', function (e) {
        if (e.target.type === 'number' && ['e', 'E', '+', '-'].includes(e.key)) { e.preventDefault(); }
    });
    body.addEventListener('wheel', function (e) {
        if (e.target.type === 'number' && document.activeElement === e.target) { e.target.blur(); }
    }, { passive: true });
    body.addEventListener('paste', function (e) {
        if (e.target.type !== 'number') { return; }
        const text = (e.clipboardData || window.clipboardData).getData('text').trim();
        if (!/^\d*\.?\d*$/.test(text)) { e.preventDefault(); }
    });
    document.querySelectorAll('[data-col-toggle]').forEach(function (box) {
        box.addEventListener('change', function () { applyColumn(box.dataset.colToggle); recalc(); });
    });
    applyAll();
})();
</script>
@endpush
