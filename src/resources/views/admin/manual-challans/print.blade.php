@extends('printMaster2')

@section('title', 'Delivery Challan — ' . $challan->challan_no)

@push('css')
<style>
    /* No page size here: the browser's print dialog then offers Layout (Portrait / Landscape) and Paper size. */
    @page { margin: 10mm; }
    .mc-head { position: relative; text-align: center; margin-bottom: 4px; min-height: 85px; }
    .mc-head .mc-logo { position: absolute; left: 0; top: 0; height: 85px; max-width: 240px; object-fit: contain; }
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
    .mc-page { width: 100%; border-collapse: collapse; }
    .mc-page > tbody > tr > td, .mc-page > tfoot > tr > td { padding: 0; border: 0; }
    .mc-sign-space { display: none; }
    .mc-sign { display: flex; justify-content: space-between; margin-top: 60px; font-size: 12px; background: #fff; }
    .mc-sign .box { width: 28%; text-align: center; }
    .mc-sign .slot { height: 50px; display: flex; align-items: flex-end; justify-content: center; }
    .mc-sign .slot img { max-height: 48px; max-width: 100%; }
    .mc-sign .line { border-top: 1px solid #333; padding-top: 4px; font-weight: 600; }
    /* On paper the signatures sit at the foot of the page; the tfoot spacer (repeated on every page) keeps the table from running under them. */
    @media print {
        .mc-sign { position: fixed; left: 0; right: 0; bottom: 0; margin: 0; }
        .mc-sign-space { display: block; height: 90px; }
    }
</style>
@endpush

@section('contents')
<div class="mc-head">
    @if(function_exists('report_logo_url') && report_logo_url())<img src="{{ report_logo_url() }}" alt="Logo" class="mc-logo">@endif
    <div class="mc-name" style="padding-top: 8px;">{{ strtoupper(config('sfl-inventory.company.name')) }}</div>
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

<table class="mc-page">
    <tbody><tr><td>
        @include('sfl-inventory::admin.manual-challans.partials.' . $type . '-table', ['class' => 'mc-table', 'minRows' => 10])
        <div class="mc-note">Received the above goods in good condition</div>
    </td></tr></tbody>
    <tfoot><tr><td><div class="mc-sign-space"></div></td></tr></tfoot>
</table>

<div class="mc-sign">
    <div class="box">
        <div class="slot"></div>
        <div class="line">Receiver's Signature</div>
    </div>
    <div class="box">
        <div class="slot"></div>
        <div class="line">Prepared By - Warehouse Incharge</div>
    </div>
    <div class="box">
        <div class="slot">@if($challan->isApproved() && $challan->approver?->signature)<img src="{{ asset($challan->approver->signature) }}" alt="Signature">@endif</div>
        <div class="line">Authorised Signature</div>
    </div>
</div>
@endsection
