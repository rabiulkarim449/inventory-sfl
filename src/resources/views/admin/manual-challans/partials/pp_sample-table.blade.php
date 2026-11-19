{{-- props: challan, class (table class), minRows (blank rows to pad a printed sheet). PP Sample lines. --}}
@php $q = fn ($v) => (float) $v ? inv_qty($v) : ''; @endphp
<table class="{{ $class ?? 'table table-bordered table-sm' }}">
    <thead>
        <tr>
            <th>SL NO.</th>
            <th>PO NUMBER</th>
            <th>ITEM CODE</th>
            <th>SAMPLE OF<br>BUYER</th>
            <th>PP SAMPLE<br>FROM SFL</th>
            <th>SIZE</th>
            <th>TOTAL QTY.</th>
            <th>REMARKS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($challan->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->po_no }}</td>
                <td>{{ $item->item_code }}</td>
                <td>{{ $q($item->buyer_sample_qty) }}</td>
                <td>{{ $q($item->sfl_sample_qty) }}</td>
                <td>{{ $item->size }}</td>
                <td>{{ inv_qty($item->qty) }}</td>
                <td class="text-left">{{ $item->remarks }}</td>
            </tr>
        @endforeach
        @for($i = $challan->items->count(); $i < ($minRows ?? 0); $i++)
            <tr>@for($c = 0; $c < 8; $c++)<td>&nbsp;</td>@endfor</tr>
        @endfor
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="text-right">Total-</td>
            <td>{{ inv_qty($challan->totalQty()) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
