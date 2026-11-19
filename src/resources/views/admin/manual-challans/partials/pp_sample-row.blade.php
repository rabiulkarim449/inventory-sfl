{{-- props: index, line (old / saved values). PP Sample line; Total Qty is worked out from the two sample columns. --}}
@php
    $n = "items[$index]";
    $q = fn ($k) => isset($line[$k]) && (float) $line[$k] ? inv_qty($line[$k]) : '';
@endphp
<tr>
    <td class="text-center" data-sl></td>
    <td><input type="text" name="{{ $n }}[po_no]" class="form-control form-control-sm" value="{{ $line['po_no'] ?? '' }}" maxlength="100"></td>
    <td><input type="text" name="{{ $n }}[item_code]" class="form-control form-control-sm" value="{{ $line['item_code'] ?? '' }}" maxlength="100"></td>
    <td class="mc-qty"><input type="number" step="any" min="0" name="{{ $n }}[buyer_sample_qty]" data-part class="form-control form-control-sm" value="{{ $q('buyer_sample_qty') }}"></td>
    <td class="mc-qty"><input type="number" step="any" min="0" name="{{ $n }}[sfl_sample_qty]" data-part class="form-control form-control-sm" value="{{ $q('sfl_sample_qty') }}"></td>
    <td><input type="text" name="{{ $n }}[size]" class="form-control form-control-sm" value="{{ $line['size'] ?? '' }}" maxlength="50" style="width:80px"></td>
    <td class="mc-qty"><input type="number" step="any" min="0" name="{{ $n }}[qty]" data-qty class="form-control form-control-sm" value="{{ $q('qty') }}" readonly tabindex="-1"></td>
    <td><input type="text" name="{{ $n }}[remarks]" class="form-control form-control-sm" value="{{ $line['remarks'] ?? '' }}" maxlength="255"></td>
    <td class="text-center"><button type="button" class="btn-custom ui-act ui-act-delete" data-remove title="Remove line"><i class="fa-solid fa-xmark"></i></button></td>
</tr>
