@extends(adminTheme() . 'layouts.app')

@php
    $editing = $challan->exists;
    $optional = ! empty($info['optional']);
    $showOptional = (bool) old('show_optional', $challan->show_optional);
    $lines = old('items', $editing ? $challan->items->map(fn ($i) => $i->only(['description', 'po_no', 'style', 'item_code', 'color', 'size', 'sizes', 'qty', 'unit', 'hanger_carton', 'remarks']))->all() : []);
    if (empty($lines)) {
        $lines = [[]];
    }
    $pageTitle = ($editing ? 'Edit ' : 'New ') . $info['title'];
@endphp

@section('title')
    <title>{{ websiteTitle($pageTitle) }}</title>
@endsection

@push('css')
<style>
    /* Field colours as on the client's sheet: blue = automatic, yellow = typed, green = optional. */
    .mc-auto { background: #dbeafe !important; }
    .mc-typed-label { background: #fef08a; padding: 0 4px; border-radius: 3px; }
    .mc-optional { background: #dcfce7 !important; }
    .mc-lines th { white-space: nowrap; font-size: 12px; vertical-align: middle; text-align: center; }
    .mc-lines td { padding: 2px; vertical-align: middle; }
    .mc-lines input { min-width: 60px; }
    .mc-lines .mc-size input { min-width: 46px; width: 52px; text-align: right; }
    .mc-lines .mc-qty input { width: 80px; text-align: right; }
    .mc-hide-optional .mc-opt { display: none; }
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

            <form method="POST" action="{{ $editing ? route('inventory.manual-challans.update', [$type, $challan]) : route('inventory.manual-challans.store', $type) }}" id="mcForm" class="{{ $showOptional ? '' : 'mc-hide-optional' }}">
                @csrf
                @if($editing) @method('PUT') @endif

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Challan No</label>
                        <input type="text" class="form-control form-control-sm mc-auto" value="{{ $editing ? $challan->challan_no : 'Given on Submit' }}" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        @if($editing)
                            <input type="text" class="form-control form-control-sm mc-auto" value="{{ $challan->challan_date->format('d M Y') }}" readonly>
                            <div class="form-text">Fixed with the Challan No.</div>
                        @else
                            <input type="date" name="challan_date" class="form-control form-control-sm mc-auto" value="{{ old('challan_date', $challan->challan_date?->format('Y-m-d')) }}" required>
                            <div class="form-text">Today by default — pick an earlier date for an old challan.</div>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><span class="mc-typed-label">To</span> <span class="text-danger">*</span></label>
                        <input type="text" name="to_name" class="form-control form-control-sm" value="{{ old('to_name', $challan->to_name) }}" required maxlength="255">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label"><span class="mc-typed-label">Address</span></label>
                        <input type="text" name="to_address" class="form-control form-control-sm" value="{{ old('to_address', $challan->to_address) }}" maxlength="255">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><span class="mc-typed-label">Contact No</span></label>
                        <input type="text" name="to_contact" class="form-control form-control-sm" value="{{ old('to_contact', $challan->to_contact) }}" maxlength="100">
                    </div>
                </div>

                @if($optional)
                    <div class="custom-control custom-switch mb-2">
                        <input type="hidden" name="show_optional" value="0">
                        <input type="checkbox" name="show_optional" value="1" class="custom-control-input" id="mcShowOptional" @checked($showOptional)>
                        <label class="custom-control-label" for="mcShowOptional"><span class="mc-optional px-1">Show size-wise qty and Hanger / Carton</span></label>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-sm mc-lines">
                        <thead>
                            <tr>
                                <th style="width:40px">SL</th>
                                <th>Description of Item</th>
                                <th>PO Number</th>
                                <th>Style</th>
                                <th>Color</th>
                                @foreach($sizes as $size)
                                    <th class="mc-opt mc-optional">{{ $size }}</th>
                                @endforeach
                                <th>Total Qty</th>
                                <th>Unit</th>
                                <th class="mc-opt mc-optional">Hanger / Carton</th>
                                <th>Remarks</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="mcRows">
                            @foreach($lines as $i => $line)
                                @include('sfl-inventory::admin.manual-challans.partials.shipment-row', ['index' => $i, 'line' => $line])
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="5" class="text-right">Total</td>
                                @foreach($sizes as $size)
                                    <td class="mc-opt text-right" data-size-total="{{ $size }}"></td>
                                @endforeach
                                <td class="text-right" id="mcGrandTotal"></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <template id="mcRowTemplate">
                    @include('sfl-inventory::admin.manual-challans.partials.shipment-row', ['index' => '__INDEX__', 'line' => []])
                </template>
                <button type="button" class="btn btn-sm ui-act ui-act-create" id="mcAddRow" title="Add Line"><i class="fa-solid fa-plus"></i> Add Line</button>

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
    const form = document.getElementById('mcForm');
    const body = document.getElementById('mcRows');
    const sizes = @json($sizes);
    let next = 100000;

    function num(v) { const n = parseFloat(v); return isNaN(n) ? 0 : n; }
    function fmt(n) { return n ? (Math.round(n * 100) / 100).toString() : ''; }
    function optionalOn() { return !form.classList.contains('mc-hide-optional'); }

    function recalc() {
        const sizeTotals = {};
        let grand = 0;
        body.querySelectorAll('tr').forEach(function (row, i) {
            row.querySelector('[data-sl]').textContent = i + 1;
            const qty = row.querySelector('[data-qty]');
            if (optionalOn()) {
                let sum = 0;
                sizes.forEach(function (s) {
                    const v = num(row.querySelector('[data-size="' + s + '"]').value);
                    sum += v;
                    sizeTotals[s] = (sizeTotals[s] || 0) + v;
                });
                // Size-wise qty typed: Total Qty is their sum.
                qty.readOnly = sum > 0;
                if (sum > 0) { qty.value = fmt(sum); }
            } else {
                qty.readOnly = false;
            }
            grand += num(qty.value);
        });
        sizes.forEach(function (s) {
            const cell = document.querySelector('[data-size-total="' + s + '"]');
            if (cell) { cell.textContent = fmt(sizeTotals[s] || 0); }
        });
        document.getElementById('mcGrandTotal').textContent = fmt(grand);
    }

    document.getElementById('mcAddRow').addEventListener('click', function () {
        const html = document.getElementById('mcRowTemplate').innerHTML.replace(/__INDEX__/g, next++);
        body.insertAdjacentHTML('beforeend', html);
        recalc();
    });
    body.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove]');
        if (!btn) { return; }
        if (body.querySelectorAll('tr').length > 1) { btn.closest('tr').remove(); } else { btn.closest('tr').querySelectorAll('input').forEach(function (i) { i.value = i.dataset.default || ''; }); }
        recalc();
    });
    body.addEventListener('input', recalc);
    const toggle = document.getElementById('mcShowOptional');
    if (toggle) {
        toggle.addEventListener('change', function () { form.classList.toggle('mc-hide-optional', !toggle.checked); recalc(); });
    }
    recalc();
})();
</script>
@endpush
