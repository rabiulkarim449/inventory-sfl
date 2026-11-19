{{-- props: challan, class (table class), minRows (blank rows to pad a printed sheet). Marketing & Development Sample lines. --}}
<table class="{{ $class ?? 'table table-bordered table-sm' }}">
    <thead>
        <tr>
            <th>SL NO.</th>
            <th>DESCRIPTION OF<br>ITEM</th>
            <th>STYLE</th>
            <th>SIZE</th>
            <th>COLOR</th>
            <th>QTY.</th>
            <th>REMARKS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($challan->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->style }}</td>
                <td>{{ $item->size }}</td>
                <td>{{ $item->color }}</td>
                <td>{{ inv_qty($item->qty) }}</td>
                <td>{{ $item->remarks }}</td>
            </tr>
        @endforeach
        @for($i = $challan->items->count(); $i < ($minRows ?? 0); $i++)
            <tr>@for($c = 0; $c < 7; $c++)<td>&nbsp;</td>@endfor</tr>
        @endfor
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-right">Total-</td>
            <td>{{ inv_qty($challan->totalQty()) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
