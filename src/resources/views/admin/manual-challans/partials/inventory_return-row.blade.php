{{-- props: index, line (old / saved values). Inventory & Return line. --}}
@php $n = "items[$index]"; @endphp
<tr>
    <td class="text-center" data-sl></td>
    <td><input type="text" name="{{ $n }}[po_no]" class="form-control form-control-sm" value="{{ $line['po_no'] ?? '' }}" maxlength="100"></td>
    <td><input type="text" name="{{ $n }}[description]" class="form-control form-control-sm" value="{{ $line['description'] ?? '' }}" maxlength="255"></td>
    <td><input type="text" name="{{ $n }}[item_code]" class="form-control form-control-sm" value="{{ $line['item_code'] ?? '' }}" maxlength="100"></td>
    <td><input type="text" name="{{ $n }}[color]" class="form-control form-control-sm" value="{{ $line['color'] ?? '' }}" maxlength="100"></td>
    <td class="mc-qty"><input type="number" step="any" min="0" name="{{ $n }}[qty]" data-qty class="form-control form-control-sm" value="{{ isset($line['qty']) && (float) $line['qty'] ? inv_qty($line['qty']) : '' }}"></td>
    <td><input type="text" name="{{ $n }}[unit]" data-default="PCS" class="form-control form-control-sm" value="{{ $line['unit'] ?? 'PCS' }}" maxlength="30" style="width:90px"></td>
    <td><input type="text" name="{{ $n }}[remarks]" class="form-control form-control-sm" value="{{ $line['remarks'] ?? '' }}" maxlength="255"></td>
    <td class="text-center"><button type="button" class="btn-custom ui-act ui-act-delete" data-remove title="Remove line"><i class="fa-solid fa-xmark"></i></button></td>
</tr>
