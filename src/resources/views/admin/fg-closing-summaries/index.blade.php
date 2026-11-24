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
            <h4 class="mb-0">Style Closing Summary <small class="text-muted">(Finished Goods)</small></h4>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-4 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="PO No, Order No, Buyer, Style" value="{{ request('search') }}">
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-sm ui-act ui-act-filter" title="Filter"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="{{ route('inventory.fg-closing-summaries.index') }}" class="btn btn-sm ui-act ui-act-reset" title="Reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Last Activity</th><th>PO No</th><th>Order No</th><th>Buyer</th><th>Style</th><th class="text-right">BOM Qty</th><th class="text-right">Ship Qty</th><th class="text-right">Short/Exc</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($summaries as $row)
                            @php $se = $row['ship'] - $row['bom']; @endphp
                            <tr>
                                <td>{{ $loop->iteration + $summaries->firstItem() - 1 }}</td>
                                <td>{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') : '' }}</td>
                                <td class="font-weight-bold">{{ $row['po_no'] }}</td>
                                <td>{{ $row['order_no'] }}</td>
                                <td>{{ $row['buyer'] }}</td>
                                <td>{{ $row['styles'] }}</td>
                                <td class="text-right">{{ inv_qty($row['bom']) }}</td>
                                <td class="text-right">{{ inv_qty($row['ship']) }}</td>
                                <td class="text-right {{ $se < 0 ? 'text-danger' : '' }}">{{ ($se > 0 ? '+' : '') . inv_qty($se) }}</td>
                                <td class="text-right text-nowrap">
                                    @can('inv_fg_closing_summary.view')
                                        <a href="{{ route('inventory.fg-closing-summaries.show', $row['key']) }}" class="btn-custom ui-act ui-act-view" title="View"><i class="fa-solid fa-eye"></i></a>
                                    @endcan
                                    @can('inv_fg_closing_summary.print')
                                        <a href="{{ route('inventory.fg-closing-summaries.print', $row['key']) }}" target="_blank" class="btn-custom ui-act ui-act-print" title="Print"><i class="fa-solid fa-print"></i></a>
                                    @endcan
                                    @can('inv_fg_closing_summary.edit')
                                        <a href="{{ route('inventory.fg-closing-summaries.edit', $row['key']) }}" class="btn-custom ui-act ui-act-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">No PO with a Finished Goods receive or shipment found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $summaries->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
