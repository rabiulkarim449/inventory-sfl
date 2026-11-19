{{-- props: index, line (old / saved values), on (ticked green columns). Shipment & Sizeset line.
     A green cell (data-col) is read-only and empty until its column is ticked in the header. --}}
@php $n = "items[$index]"; @endphp
<tr>
    <td class="text-center" data-sl></td>
    <td><input type="text" name="{{ $n }}[description]" class="form-control form-control-sm" value="{{ $line['description'] ?? '' }}" maxlength="255"></td>
    <td><input type="text" name="{{ $n }}[po_no]" class="form-control form-control-sm" value="{{ $line['po_no'] ?? '' }}" maxlength="100"></td>
    <td><input type="text" name="{{ $n }}[style]" class="form-control form-control-sm" value="{{ $line['style'] ?? '' }}" maxlength="100"></td>
    <td><input type="text" name="{{ $n }}[color]" class="form-control form-control-sm" value="{{ $line['color'] ?? '' }}" maxlength="100"></td>
    @foreach(\ME\SflInventory\Support\ManualChallanTypes::SIZES as $size)
        @php $live = in_array($size, $on, true); $v = $live ? ($line['sizes'][$size] ?? '') : ''; @endphp
        <td class="mc-size"><input type="number" step="any" min="0" name="{{ $n }}[sizes][{{ $size }}]" data-col="{{ $size }}" data-size="{{ $size }}" class="form-control form-control-sm" value="{{ $v !== '' && (float) $v ? inv_qty($v) : '' }}" @readonly(! $live) tabindex="{{ $live ? 0 : -1 }}"></td>
    @endforeach
    <td class="mc-qty"><input type="number" step="any" min="0" name="{{ $n }}[qty]" data-qty class="form-control form-control-sm" value="{{ isset($line['qty']) && (float) $line['qty'] ? inv_qty($line['qty']) : '' }}"></td>
    <td><input type="text" name="{{ $n }}[unit]" data-default="PCS" class="form-control form-control-sm" value="{{ $line['unit'] ?? 'PCS' }}" maxlength="30" style="width:70px"></td>
    @php $live = in_array('hanger_carton', $on, true); @endphp
    <td><input type="text" name="{{ $n }}[hanger_carton]" data-col="hanger_carton" class="form-control form-control-sm" value="{{ $live ? ($line['hanger_carton'] ?? '') : '' }}" maxlength="50" @readonly(! $live) tabindex="{{ $live ? 0 : -1 }}"></td>
    <td><input type="text" name="{{ $n }}[remarks]" class="form-control form-control-sm" value="{{ $line['remarks'] ?? '' }}" maxlength="255"></td>
    <td class="text-center"><button type="button" class="btn-custom ui-act ui-act-delete" data-remove title="Remove line"><i class="fa-solid fa-xmark"></i></button></td>
</tr>
