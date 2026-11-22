{{--
    The Style Closing Summary sheet, laid out like the client's Excel (8 columns A–H).
    Used by Show and Print. Needs: $summary (ClosingSummary with products).
    Factory Name / Location come from the inventory config (auto); the rest is as typed.
--}}
@php
    $trimRows = \ME\SflInventory\Support\ClosingSummaryTrims::rows();
    $logo = \ME\SflInventory\Models\ClosingSummary::logoSrc($summary->logo);
    $qty = fn ($v) => $v === null ? '' : (fmod((float) $v, 1) == 0 ? number_format((float) $v) : number_format((float) $v, 2));
@endphp
<table class="cs-sheet">
    <colgroup>
        <col style="width:14%"><col style="width:12%"><col style="width:7%"><col style="width:12%"><col style="width:7%"><col style="width:10%"><col style="width:8%"><col style="width:30%">
    </colgroup>
    <tbody>
        <tr>
            <td colspan="3" rowspan="4" class="cs-logo">@if($logo)<img src="{{ $logo }}" alt="Logo">@endif</td>
            <td colspan="2" rowspan="4" class="cs-blank"></td>
            <td colspan="2" class="cs-lbl">Factory Name :</td>
            <td class="cs-val">{{ $summary->factory_name }}</td>
        </tr>
        <tr>
            <td colspan="2" class="cs-lbl">Factory Location :</td>
            <td class="cs-val">{{ $summary->factory_location }}</td>
        </tr>
        <tr>
            <td colspan="2" class="cs-lbl">Purchase Order No (PO) :</td>
            <td class="cs-val">{{ $summary->po_no }}</td>
        </tr>
        <tr>
            <td colspan="2" class="cs-lbl">Report Date :</td>
            <td class="cs-val">{{ $summary->report_date?->format('d.m.y') }}</td>
        </tr>
        <tr><td colspan="8" class="cs-title">Style Closing Summary</td></tr>

        @foreach($summary->products as $p)
            @php $se = $p->shortExcess(); @endphp
            <tr class="cs-head">
                <td>Product Code:</td>
                <td>BOM Qty:</td><td class="cs-num">{{ $qty($p->bom_qty) }}</td>
                <td>Ship Qty:</td><td class="cs-num">{{ $qty($p->ship_qty) }}</td>
                <td>Short/Excess</td><td class="cs-num">{{ $se === null ? '' : ($se > 0 ? '+' : '') . $qty($se) }}</td>
                <td>Remarks</td>
            </tr>
            <tr>
                <td rowspan="{{ count($trimRows) + 1 }}" class="cs-product">{!! nl2br(e($p->product)) !!}</td>
                <td colspan="6" class="cs-band">Trims and Accessories Details</td>
                <td rowspan="{{ count($trimRows) + 1 }}" class="cs-remarks">{!! nl2br(e($p->remarks)) !!}</td>
            </tr>
            @foreach($trimRows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td class="cs-trim">{{ $cell[1] ?? '' }}</td>
                        <td class="cs-num">{{ $cell ? $p->trim($cell[0]) : '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        @endforeach

        <tr>
            <td colspan="8" class="cs-comments"><strong>Comments:</strong> {!! nl2br(e($summary->comments)) !!}</td>
        </tr>
    </tbody>
</table>
