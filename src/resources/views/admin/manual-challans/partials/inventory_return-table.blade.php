{{-- props: challan, class (table class), minRows (blank rows to pad a printed sheet). Inventory & Return lines. --}}
@php $unit = $challan->items->pluck('unit')->filter()->unique(); @endphp
<table class="{{ $class ?? 'table table-bordered table-sm' }}">
    <thead>
        <tr>
            <th>SL NO.</th>
            <th>PO NO/<br>CHALLAN NO</th>
            <th>DESCRIPTION OF ITEM</th>
            <th>ITEM CODE</th>
            <th>COLOR</th>
            <th>QTY.</th>
            <th>UNIT OF<br>MEASUREMENT</th>
            <th>REMARKS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($challan->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->po_no }}</td>
                <td class="text-left">{{ $item->description }}</td>
                <td>{{ $item->item_code }}</td>
                <td>{{ $item->color }}</td>
                <td>{{ inv_qty($item->qty) }}</td>
                <td>{{ $item->unit }}</td>
                <td class="text-left">{{ $item->remarks }}</td>
            </tr>
        @endforeach
        @for($i = $challan->items->count(); $i < ($minRows ?? 0); $i++)
            <tr>@for($c = 0; $c < 8; $c++)<td>&nbsp;</td>@endfor</tr>
        @endfor
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-right">Total-</td>
            <td>{{ inv_qty($challan->totalQty()) }}</td>
            <td>{{ $unit->count() === 1 ? $unit->first() : '' }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
