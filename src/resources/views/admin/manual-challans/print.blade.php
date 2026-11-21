@extends('printMaster2')

@section('title', 'Delivery Challan — ' . $challan->challan_no)

@push('css')
<style>
    /* No page size here: the browser's print dialog then offers Layout (Portrait / Landscape) and Paper size. */
    @page { margin: 10mm; }
    .mc-head { text-align: center; margin-bottom: 4px; }
    .mc-head .mc-name { font-size: 22px; font-weight: 800; letter-spacing: .5px; }
    .mc-head .mc-addr { font-size: 11px; font-weight: 600; }
    .mc-title { text-align: center; font-size: 18px; font-weight: 800; margin: 8px 0 10px; }
    .mc-meta { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
    .mc-meta .row { display: flex; margin-bottom: 2px; }
    .mc-meta .lbl { width: 85px; flex-shrink: 0; font-weight: 700; }
    .mc-status { font-size: 11px; font-weight: 700; color: #b45309; }
    .mc-table { width: 100%; border-collapse: collapse; }
    .mc-table th, .mc-table td { border: 1px solid #333; padding: 4px 5px; font-size: 11px; text-align: center; }
    .mc-table thead th { font-weight: 700; }
    .mc-table td.text-left { text-align: left; }
    .mc-table td.text-right { text-align: right; }
    .mc-table tfoot td { font-weight: 700; }
    .mc-note { margin-top: 8px; font-size: 12px; }
    .mc-sign { display: flex; justify-content: space-between; margin-top: 50px; font-size: 12px; }
    .mc-sign .box { width: 22%; text-align: center; }
    .mc-sign .slot { height: 50px; display: flex; align-items: flex-end; justify-content: center; }
    .mc-sign .slot img { max-height: 48px; max-width: 100%; }
    .mc-sign .line { border-top: 1px solid #333; padding-top: 4px; font-weight: 600; }
</style>
@endpush

@section('contents')
<div class="mc-head">
    @if(function_exists('report_logo_url') && report_logo_url())<img src="{{ report_logo_url() }}" alt="Logo" style="display:block;height:50px;max-width:160px;object-fit:contain;margin:0 auto 4px;">@endif
    <div class="mc-name">{{ strtoupper(config('sfl-inventory.company.name')) }}</div>
    <div class="mc-addr">{{ config('sfl-inventory.company.address') }}</div>
</div>

<div class="mc-title">Delivery Challan</div>

<div class="mc-meta">
    <div>
        <div class="row"><div class="lbl">Challan No</div><div>: {{ $challan->challan_no }}</div></div>
        <div class="row"><div class="lbl">To</div><div>: {{ $challan->to_name }}</div></div>
        <div class="row"><div class="lbl">Address</div><div>: {{ $challan->to_address }}</div></div>
        <div class="row"><div class="lbl">Contact No</div><div>: {{ $challan->to_contact }}</div></div>
    </div>
    <div class="text-right">
        <div><strong>Date:</strong> {{ $challan->challan_date->format('d.m.y') }}</div>
        @unless($challan->isApproved())
            <div class="mc-status">{{ strtoupper(\ME\SflInventory\Models\ManualChallan::STATUSES[$challan->status][0] ?? $challan->status) }}{{ $challan->isPending() ? ' APPROVAL' : '' }}</div>
        @endunless
    </div>
</div>

@include('sfl-inventory::admin.manual-challans.partials.' . $type . '-table', ['class' => 'mc-table', 'minRows' => 10])

<div class="mc-note">Received the above goods in good condition</div>

<div class="mc-sign">
    <div class="box">
        <div class="slot"></div>
        <div class="line">Receiver's Signature</div>
    </div>
    <div class="box">
        <div class="slot">@if($challan->creator?->signature)<img src="{{ asset($challan->creator->signature) }}" alt="Signature">@endif</div>
        <div class="line">Prepared By{{ $challan->creator ? ' — ' . $challan->creator->name : '' }}</div>
    </div>
    <div class="box">
        <div class="slot"></div>
        <div class="line">Checked By</div>
    </div>
    <div class="box">
        <div class="slot">@if($challan->isApproved() && $challan->approver?->signature)<img src="{{ asset($challan->approver->signature) }}" alt="Signature">@endif</div>
        <div class="line">Approved By{{ $challan->isApproved() && $challan->approver ? ' — ' . $challan->approver->name : '' }}</div>
    </div>
</div>
@endsection
