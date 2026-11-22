@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Style Closing Summary') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Style Closing Summary</h4>
            @can('inv_closing_summary.add')
                <a href="{{ route('inventory.closing-summaries.create') }}" class="btn btn-sm ui-act ui-act-create" title="New Summary"><i class="fa-solid fa-plus"></i> New Summary</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-4 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="PO No, Product, Style" value="{{ request('search') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" title="From">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" title="To">
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-sm ui-act ui-act-filter" title="Filter"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="{{ route('inventory.closing-summaries.index') }}" class="btn btn-sm ui-act ui-act-reset" title="Reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Report Date</th><th>PO No</th><th>Product</th><th class="text-right">BOM Qty</th><th class="text-right">Ship Qty</th><th class="text-right">Short/Exc</th><th>Prepared By</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($summaries as $summary)
                            @php
                                $bom = $summary->products->sum(fn ($p) => (float) $p->bom_qty);
                                $ship = $summary->products->sum(fn ($p) => (float) $p->ship_qty);
                                $first = strtok((string) $summary->products->first()?->product, "\n");
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + $summaries->firstItem() - 1 }}</td>
                                <td>{{ $summary->report_date->format('d M Y') }}</td>
                                <td class="font-weight-bold">{{ $summary->po_no }}</td>
                                <td>{{ $first }}@if($summary->products->count() > 1) <span class="text-muted small">+{{ $summary->products->count() - 1 }} more</span>@endif</td>
                                <td class="text-right">{{ inv_qty($bom) }}</td>
                                <td class="text-right">{{ inv_qty($ship) }}</td>
                                <td class="text-right {{ $ship - $bom < 0 ? 'text-danger' : '' }}">{{ ($ship - $bom > 0 ? '+' : '') . inv_qty($ship - $bom) }}</td>
                                <td>{{ $summary->creator->name ?? '' }}</td>
                                <td class="text-right text-nowrap">
                                    @can('inv_closing_summary.view')
                                        <a href="{{ route('inventory.closing-summaries.show', $summary) }}" class="btn-custom ui-act ui-act-view" title="View"><i class="fa-solid fa-eye"></i></a>
                                    @endcan
                                    @can('inv_closing_summary.print')
                                        <a href="{{ route('inventory.closing-summaries.print', $summary) }}" target="_blank" class="btn-custom ui-act ui-act-print" title="Print"><i class="fa-solid fa-print"></i></a>
                                    @endcan
                                    @can('inv_closing_summary.edit')
                                        <a href="{{ route('inventory.closing-summaries.edit', $summary) }}" class="btn-custom ui-act ui-act-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    @endcan
                                    @can('inv_closing_summary.delete')
                                        <button type="button" class="btn-custom ui-act ui-act-delete" data-toggle="modal" data-target="#deleteClosingSummaryModal" data-action="{{ route('inventory.closing-summaries.destroy', $summary) }}" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No closing summaries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $summaries->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteClosingSummaryModal', 'label' => 'closing summary'])
@endsection
