@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Style Closing Summary') }}</title>
@endsection

@push('css')
@include('sfl-inventory::admin.fg-closing-summaries.partials.sheet-css')
@endpush

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
            <h4 class="mb-0">Style Closing Summary</h4>
            <div class="d-flex gap-1 flex-wrap">
                @can('inv_fg_closing_summary.print')
                    <a href="{{ route('inventory.fg-closing-summaries.print', $orderPo) }}" target="_blank" class="btn btn-sm ui-act ui-act-print" title="Print"><i class="fa-solid fa-print"></i> Print</a>
                @endcan
                @can('inv_fg_closing_summary.edit')
                    <a href="{{ route('inventory.fg-closing-summaries.edit', $orderPo) }}" class="btn btn-sm ui-act ui-act-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                @endcan
                <a href="{{ route('inventory.fg-closing-summaries.index') }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">Filled by the system from the order PO, its shipments and the store entries of the style (trims left); Remarks and Comments as typed · as of {{ now()->format('d M Y h:i A') }}</div>
            <div class="table-responsive">
                <div style="min-width: 900px;">
                    @include('sfl-inventory::admin.fg-closing-summaries.partials.sheet')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
