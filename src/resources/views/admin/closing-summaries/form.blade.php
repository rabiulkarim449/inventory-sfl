@extends(adminTheme() . 'layouts.app')

@php
    $editing = $summary->exists;
    $products = old('products', $editing ? $summary->products->map(fn ($p) => $p->only(['product', 'bom_qty', 'ship_qty', 'remarks', 'trims']))->all() : []);
    if (empty($products)) {
        $products = [[]];
    }
    $pageTitle = ($editing ? 'Edit ' : 'New ') . 'Style Closing Summary';
    $currentLogo = \ME\SflInventory\Models\ClosingSummary::logoSrc($summary->logo);
    // Earlier logos, with a preview each (at most 20).
    $earlier = collect($earlierLogos)->reject(fn ($p) => $p === $summary->logo)->take(20)
        ->map(fn ($p) => ['path' => $p, 'src' => \ME\SflInventory\Models\ClosingSummary::logoSrc($p)])->values();
@endphp

@section('title')
    <title>{{ websiteTitle($pageTitle) }}</title>
@endsection

@push('css')
<style>
    /* As on the client's sheet: blue = filled by the system. */
    .cs-auto { background: #dbeafe !important; }
    .cs-block-head { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
    .cs-trims th { font-size: 12px; font-weight: 600; white-space: nowrap; vertical-align: middle; width: 13%; background: #f8fafc; }
    .cs-trims td { padding: 2px; width: 20%; }
    .cs-trims td > input { width: 100% !important; }
    .cs-logo-box { width: 180px; height: 90px; border: 1px dashed #cbd5e1; border-radius: 6px; display: flex; align-items: center; justify-content: center; background: #fff; overflow: hidden; }
    .cs-logo-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .cs-logo-pick { display: inline-flex; border: 2px solid #e2e8f0; border-radius: 6px; padding: 2px; cursor: pointer; background: #fff; }
    .cs-logo-pick img { height: 38px; max-width: 90px; object-fit: contain; }
    .cs-logo-pick.active { border-color: #2563eb; }
    input[type=number] { -moz-appearance: textfield; appearance: textfield; }
    input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>
@endpush

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
            <a href="{{ route('inventory.closing-summaries.index') }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger small"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('inventory.closing-summaries.update', $summary) : route('inventory.closing-summaries.store') }}" id="csForm">
                @csrf
                @if($editing) @method('PUT') @endif
                <input type="hidden" name="logo_pick" id="csLogoPick" value="keep">

                {{-- As on the sheet: the logo on the left, factory / PO / date on the right. --}}
                <div class="row mb-2">
                    <div class="col-lg-6 mb-3">
                        <label class="small mb-1 d-block">Logo <span class="text-muted">(jpg / png, up to 2 MB)</span></label>
                        <div class="d-flex align-items-start flex-wrap gap-2">
                            <div class="cs-logo-box" id="csLogoPreview">
                                @if($currentLogo)<img src="{{ $currentLogo }}" alt="Logo">@else<span class="small text-muted">No logo</span>@endif
                            </div>
                            <div>
                                <input type="file" name="logo_file" id="csLogoFile" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="form-control form-control-sm mb-1" style="max-width:260px">
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" id="csLogoNone"><i class="fa-solid fa-xmark"></i> No logo</button>
                            </div>
                        </div>
                        @if($earlier->isNotEmpty())
                            <div class="small text-muted mt-2 mb-1">Or pick one used before:</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($earlier as $l)
                                    <span class="cs-logo-pick" data-logo-path="{{ $l['path'] }}" title="Use this logo"><img src="{{ $l['src'] }}" alt="Logo"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-5 offset-lg-1">
                        <div class="form-group row mb-2">
                            <label class="col-sm-5 col-form-label col-form-label-sm">Factory Name</label>
                            <div class="col-sm-7"><input type="text" name="factory_name" class="form-control form-control-sm" maxlength="255" value="{{ old('factory_name', $summary->factory_name) }}"></div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-5 col-form-label col-form-label-sm">Factory Location</label>
                            <div class="col-sm-7"><input type="text" name="factory_location" class="form-control form-control-sm" maxlength="255" value="{{ old('factory_location', $summary->factory_location) }}"></div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-5 col-form-label col-form-label-sm">Purchase Order No (PO)</label>
                            <div class="col-sm-7"><input type="text" name="po_no" class="form-control form-control-sm" maxlength="100" value="{{ old('po_no', $summary->po_no) }}"></div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-5 col-form-label col-form-label-sm">Report Date</label>
                            <div class="col-sm-7">
                                {{-- Set by the system: the day the summary is first saved. --}}
                                <input type="text" class="form-control form-control-sm cs-auto" value="{{ ($summary->report_date ?? today())->format('d M Y') }}" readonly tabindex="-1">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="csBlocks">
                    @foreach(array_values($products) as $i => $p)
                        @include('sfl-inventory::admin.closing-summaries.partials.product-block', ['i' => $i, 'p' => $p])
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm ui-act ui-act-create" id="csAddBlock" title="Add Product"><i class="fa-solid fa-plus"></i> Add Product</button>

                <div class="form-group mt-3">
                    <label class="small mb-1">Comments</label>
                    <textarea name="comments" rows="3" class="form-control form-control-sm" maxlength="5000">{{ old('comments', $summary->comments) }}</textarea>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-sm ui-act ui-act-save" title="Save"><i class="fa-solid fa-floppy-disk"></i> {{ $editing ? 'Update' : 'Save' }}</button>
                    <a href="{{ route('inventory.closing-summaries.index') }}" class="btn btn-sm ui-act ui-act-cancel" title="Cancel"><i class="fa-solid fa-ban"></i> Cancel</a>
                </div>
            </form>

            <template id="csBlockTemplate">
                @include('sfl-inventory::admin.closing-summaries.partials.product-block', ['i' => '__INDEX__', 'p' => []])
            </template>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    const blocks = document.getElementById('csBlocks');
    let next = 100000;

    function num(v) { const n = parseFloat(v); return isNaN(n) ? null : n; }

    function recalc() {
        blocks.querySelectorAll('[data-block]').forEach(function (b, i) {
            b.querySelector('[data-block-no]').textContent = i + 1;
            const bom = num(b.querySelector('[data-bom]').value);
            const ship = num(b.querySelector('[data-ship]').value);
            const out = b.querySelector('[data-short]');
            // Short / Excess = Ship − BOM (minus = short).
            if (bom === null || ship === null) { out.value = ''; return; }
            const d = Math.round((ship - bom) * 100) / 100;
            out.value = (d > 0 ? '+' : '') + d;
            out.classList.toggle('text-danger', d < 0);
        });
    }

    document.getElementById('csAddBlock').addEventListener('click', function () {
        blocks.insertAdjacentHTML('beforeend', document.getElementById('csBlockTemplate').innerHTML.replace(/__INDEX__/g, next++));
        recalc();
    });
    blocks.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove-block]');
        if (!btn) { return; }
        const block = btn.closest('[data-block]');
        if (blocks.querySelectorAll('[data-block]').length > 1) {
            block.remove();
        } else {
            block.querySelectorAll('input:not([readonly]), textarea').forEach(function (i) { i.value = ''; });
        }
        recalc();
    });
    blocks.addEventListener('input', recalc);
    // Number boxes: digits and one point only, no change on mouse wheel.
    blocks.addEventListener('keydown', function (e) {
        if (e.target.type === 'number' && ['e', 'E', '+', '-'].includes(e.key)) { e.preventDefault(); }
    });
    blocks.addEventListener('wheel', function (e) {
        if (e.target.type === 'number' && document.activeElement === e.target) { e.target.blur(); }
    }, { passive: true });

    // Logo: a new file, one used before, or none.
    const pick = document.getElementById('csLogoPick');
    const preview = document.getElementById('csLogoPreview');
    const file = document.getElementById('csLogoFile');
    function show(src) { preview.innerHTML = src ? '<img alt="Logo">' : '<span class="small text-muted">No logo</span>'; if (src) { preview.firstChild.src = src; } }
    function clearPicks() { document.querySelectorAll('.cs-logo-pick').forEach(function (p) { p.classList.remove('active'); }); }
    file.addEventListener('change', function () {
        clearPicks();
        pick.value = 'keep';
        if (file.files[0]) { show(URL.createObjectURL(file.files[0])); }
    });
    document.querySelectorAll('.cs-logo-pick').forEach(function (p) {
        p.addEventListener('click', function () {
            clearPicks();
            p.classList.add('active');
            file.value = '';
            pick.value = p.dataset.logoPath;
            show(p.querySelector('img').src);
        });
    });
    document.getElementById('csLogoNone').addEventListener('click', function () {
        clearPicks();
        file.value = '';
        pick.value = 'none';
        show(null);
    });

    recalc();
})();
</script>
@endpush
