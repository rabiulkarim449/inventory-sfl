@extends('printMaster2')

@section('title', 'Style Closing Summary' . ($summary->po_no ? ' — ' . $summary->po_no : ''))

@push('css')
@include('sfl-inventory::admin.fg-closing-summaries.partials.sheet-css')
<style>
    /* No page size: the print dialog then offers Layout (Portrait / Landscape). */
    @page { margin: 10mm; }
    .cs-sheet tr { page-break-inside: avoid; }
</style>
@endpush

@section('contents')
@include('sfl-inventory::admin.fg-closing-summaries.partials.sheet')
@include('sfl-inventory::admin.fg-closing-summaries.partials.signatures')
@endsection
