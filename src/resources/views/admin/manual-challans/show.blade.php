@extends(adminTheme() . 'layouts.app')

@php $perm = \ME\SflInventory\Support\ManualChallanTypes::permission($type); @endphp

@php [$statusLabel, $statusColor] = \ME\SflInventory\Models\ManualChallan::STATUSES[$challan->status] ?? [$challan->status, 'secondary']; @endphp

@section('title')
    <title>{{ websiteTitle($info['title'] . ' — ' . $challan->challan_no) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
            <h4 class="mb-0">{{ $info['title'] }} <span class="badge badge-{{ $statusColor }} align-middle">{{ $statusLabel }}</span></h4>
            <div class="d-flex gap-1 flex-wrap">
                @can($perm . '.print')
                    <a href="{{ route('inventory.manual-challans.print', [$type, $challan]) }}" target="_blank" class="btn btn-sm ui-act ui-act-print" title="Print"><i class="fa-solid fa-print"></i> Print</a>
                @endcan
                @if($challan->isPending())
                    @can($perm . '.edit')
                        <a href="{{ route('inventory.manual-challans.edit', [$type, $challan]) }}" class="btn btn-sm ui-act ui-act-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                    @endcan
                @endif
                <a href="{{ route('inventory.manual-challans.index', $type) }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row mb-3">
                <dt class="col-sm-2">Challan No</dt><dd class="col-sm-4 font-weight-bold">{{ $challan->challan_no }}</dd>
                <dt class="col-sm-2">Date</dt><dd class="col-sm-4">{{ $challan->challan_date->format('d M Y') }}</dd>
                <dt class="col-sm-2">To</dt><dd class="col-sm-4">{{ $challan->to_name }}</dd>
                <dt class="col-sm-2">Contact No</dt><dd class="col-sm-4">{{ $challan->to_contact ?: '—' }}</dd>
                <dt class="col-sm-2">Address</dt><dd class="col-sm-10">{{ $challan->to_address ?: '—' }}</dd>
                <dt class="col-sm-2">Prepared By</dt><dd class="col-sm-4">{{ $challan->creator->name ?? '—' }} <span class="text-muted small">{{ $challan->created_at?->format('d M Y h:i A') }}</span></dd>
                @unless($challan->isPending())
                    <dt class="col-sm-2">{{ $challan->isApproved() ? 'Approved By' : 'Rejected By' }}</dt>
                    <dd class="col-sm-4">{{ $challan->approver->name ?? '—' }} <span class="text-muted small">{{ $challan->approved_at?->format('d M Y h:i A') }}</span>
                        @if($challan->approval_remarks)<div class="small">{{ $challan->approval_remarks }}</div>@endif
                    </dd>
                @endunless
            </dl>

            <div class="table-responsive">
                @include('sfl-inventory::admin.manual-challans.partials.' . $type . '-table', ['class' => 'table table-bordered table-sm text-center align-middle'])
            </div>

            @if($challan->isPending())
                @can($perm . '.approve')
                    <div class="border rounded p-3 mt-3">
                        <h6 class="mb-2">Approval</h6>
                        <form method="POST" class="row align-items-end">
                            @csrf
                            <div class="col-md-6 mb-2">
                                <input type="text" name="approval_remarks" class="form-control form-control-sm" maxlength="255" placeholder="Remarks (optional)">
                            </div>
                            <div class="col-md-6 mb-2 d-flex gap-1">
                                <button type="submit" formaction="{{ route('inventory.manual-challans.approve', [$type, $challan]) }}" class="btn btn-sm ui-act ui-act-approve" title="Approve"><i class="fa-solid fa-check"></i> Approve</button>
                                <button type="submit" formaction="{{ route('inventory.manual-challans.reject', [$type, $challan]) }}" class="btn btn-sm ui-act ui-act-reject" title="Reject" onclick="return confirm('Reject this challan? It cannot be changed afterwards.')"><i class="fa-solid fa-xmark"></i> Reject</button>
                            </div>
                        </form>
                    </div>
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
