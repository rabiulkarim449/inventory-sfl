{{-- props: challan, class (table class), minRows (blank rows to pad a printed sheet).
     Shipment & Sizeset lines: only the green columns ticked on this challan appear. --}}
@php
    $sizes = $challan->optionalSizes();
    $hanger = $challan->optionalOn('hanger_carton');
    $cols = 5 + count($sizes) + 2 + ($hanger ? 1 : 0) + 1;
    $unit = $challan->items->pluck('unit')->filter()->unique();
@endphp
<table class="{{ $class ?? 'table table-bordered table-sm' }}">
    <thead>
        <tr>
            <th>SL NO.</th>
            <th>DESCRIPTION OF ITEM</th>
            <th>PO NUMBER</th>
            <th>STYLE</th>
            <th>COLOR</th>
            @foreach($sizes as $size)<th>{{ $size }}</th>@endforeach
            <th>TOTAL QTY.</th>
            <th>UNIT</th>
            @if($hanger)<th>HANGER/<br>CARTON</th>@endif
            <th>REMARKS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($challan->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="text-left">{{ $item->description }}</td>
                <td>{{ $item->po_no }}</td>
                <td>{{ $item->style }}</td>
                <td>{{ $item->color }}</td>
                @foreach($sizes as $size)<td>{{ $item->sizeQty($size) ? inv_qty($item->sizeQty($size)) : '' }}</td>@endforeach
                <td>{{ inv_qty($item->qty) }}</td>
                <td>{{ $item->unit }}</td>
                @if($hanger)<td>{{ $item->hanger_carton }}</td>@endif
                <td class="text-left">{{ $item->remarks }}</td>
            </tr>
        @endforeach
        @for($i = $challan->items->count(); $i < ($minRows ?? 0); $i++)
            <tr>@for($c = 0; $c < $cols; $c++)<td>&nbsp;</td>@endfor</tr>
        @endfor
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-right">Total</td>
            @foreach($sizes as $size)
                @php $sizeTotal = $challan->items->sum(fn ($i) => $i->sizeQty($size)); @endphp
                <td>{{ $sizeTotal ? inv_qty($sizeTotal) : '' }}</td>
            @endforeach
            <td>{{ inv_qty($challan->totalQty()) }}</td>
            <td>{{ $unit->count() === 1 ? $unit->first() : '' }}</td>
            @if($hanger)<td></td>@endif
            <td></td>
        </tr>
    </tfoot>
</table>
