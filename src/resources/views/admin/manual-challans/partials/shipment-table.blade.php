{{-- props: challan, class (table class), minRows (blank rows to pad a printed sheet). Shipment & Sizeset lines. --}}
@php
    $sizes = \ME\SflInventory\Support\ManualChallanTypes::SIZES;
    $opt = $challan->show_optional;
    $cols = 5 + ($opt ? count($sizes) : 0) + 2 + ($opt ? 1 : 0) + 1;
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
            @if($opt)
                @foreach($sizes as $size)<th>{{ $size }}</th>@endforeach
            @endif
            <th>TOTAL QTY.</th>
            <th>UNIT</th>
            @if($opt)<th>HANGER/<br>CARTON</th>@endif
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
                @if($opt)
                    @foreach($sizes as $size)<td>{{ $item->sizeQty($size) ? inv_qty($item->sizeQty($size)) : '' }}</td>@endforeach
                @endif
                <td>{{ inv_qty($item->qty) }}</td>
                <td>{{ $item->unit }}</td>
                @if($opt)<td>{{ $item->hanger_carton }}</td>@endif
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
            @if($opt)
                @foreach($sizes as $size)
                    @php $sizeTotal = $challan->items->sum(fn ($i) => $i->sizeQty($size)); @endphp
                    <td>{{ $sizeTotal ? inv_qty($sizeTotal) : '' }}</td>
                @endforeach
            @endif
            <td>{{ inv_qty($challan->totalQty()) }}</td>
            <td>{{ $unit->count() === 1 ? $unit->first() : '' }}</td>
            @if($opt)<td></td>@endif
            <td></td>
        </tr>
    </tfoot>
</table>
