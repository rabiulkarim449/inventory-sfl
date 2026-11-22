@extends(adminTheme() . 'layouts.app')

@php $perm = \ME\SflInventory\Support\ManualChallanTypes::permission($type); @endphp

@section('title')
    <title>{{ websiteTitle($info['title']) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ $info['title'] }}</h4>
            @can($perm . '.add')
                <a href="{{ route('inventory.manual-challans.create', $type) }}" class="btn btn-sm ui-act ui-act-create" title="New Challan"><i class="fa-solid fa-plus"></i> New Challan</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Challan No, To, PO, Style" value="{{ request('search') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        @foreach(\ME\SflInventory\Models\ManualChallan::STATUSES as $key => [$label])
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" title="From">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" title="To">
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-sm ui-act ui-act-filter" title="Filter"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="{{ route('inventory.manual-challans.index', $type) }}" class="btn btn-sm ui-act ui-act-reset" title="Reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Challan No</th><th>Date</th><th>To</th><th class="text-right">Lines</th><th class="text-right">Total Qty</th><th>Status</th><th>Prepared By</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($challans as $challan)
                            @php [$statusLabel, $statusColor] = \ME\SflInventory\Models\ManualChallan::STATUSES[$challan->status] ?? [$challan->status, 'secondary']; @endphp
                            <tr>
                                <td>{{ $loop->iteration + $challans->firstItem() - 1 }}</td>
                                <td class="font-weight-bold">{{ $challan->challan_no }}</td>
                                <td>{{ $challan->challan_date->format('d M Y') }}</td>
                                <td>{{ $challan->to_name }}</td>
                                <td class="text-right">{{ $challan->items_count }}</td>
                                <td class="text-right">{{ inv_qty($challan->totalQty()) }}</td>
                                <td><span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span></td>
                                <td>{{ $challan->creator->name ?? '' }}</td>
                                <td class="text-right text-nowrap">
                                    @can($perm . '.view')
                                        <a href="{{ route('inventory.manual-challans.show', [$type, $challan]) }}" class="btn-custom ui-act ui-act-view" title="View"><i class="fa-solid fa-eye"></i></a>
                                    @endcan
                                    @can($perm . '.print')
                                        <a href="{{ route('inventory.manual-challans.print', [$type, $challan]) }}" target="_blank" class="btn-custom ui-act ui-act-print" title="Print"><i class="fa-solid fa-print"></i></a>
                                    @endcan
                                    @if($challan->isPending())
                                        @can($perm . '.edit')
                                            <a href="{{ route('inventory.manual-challans.edit', [$type, $challan]) }}" class="btn-custom ui-act ui-act-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        @endcan
                                        @can($perm . '.delete')
                                            <button type="button" class="btn-custom ui-act ui-act-delete" data-toggle="modal" data-target="#deleteManualChallanModal" data-action="{{ route('inventory.manual-challans.destroy', [$type, $challan]) }}" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No challans found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $challans->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteManualChallanModal', 'label' => 'challan'])
@endsection
