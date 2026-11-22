{{-- One product block of the form. Needs: $i (index or __INDEX__), $p (array of values). --}}
@php $n = "products[$i]"; @endphp
<div class="cs-block border rounded mb-3" data-block>
    <div class="d-flex justify-content-between align-items-center px-2 py-1 cs-block-head">
        <strong class="small">Product <span data-block-no></span></strong>
        <button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove-block title="Remove product"><i class="fa-solid fa-xmark"></i> Remove</button>
    </div>
    <div class="p-2">
        <div class="row">
            <div class="col-lg-3 mb-2">
                <label class="small mb-1">Product <span class="text-muted">(name, style, PO)</span></label>
                <textarea name="{{ $n }}[product]" rows="4" class="form-control form-control-sm" maxlength="1000" placeholder="Platinum Waistcoat&#10;Style: KX-SFL-16-26&#10;PO: 267544">{{ $p['product'] ?? '' }}</textarea>
            </div>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-4 col-md-2 mb-2">
                        <label class="small mb-1">BOM Qty</label>
                        <input type="number" step="any" min="0" name="{{ $n }}[bom_qty]" class="form-control form-control-sm text-right" value="{{ $p['bom_qty'] ?? '' }}" data-bom>
                    </div>
                    <div class="col-4 col-md-2 mb-2">
                        <label class="small mb-1">Ship Qty</label>
                        <input type="number" step="any" min="0" name="{{ $n }}[ship_qty]" class="form-control form-control-sm text-right" value="{{ $p['ship_qty'] ?? '' }}" data-ship>
                    </div>
                    <div class="col-4 col-md-2 mb-2">
                        <label class="small mb-1">Short/Exc</label>
                        <input type="text" class="form-control form-control-sm text-right cs-auto" readonly tabindex="-1" data-short>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="small mb-1">Remarks</label>
                        <input type="text" name="{{ $n }}[remarks]" class="form-control form-control-sm" maxlength="2000" value="{{ $p['remarks'] ?? '' }}" placeholder="e.g. Marketing Sample 01 Pcs">
                    </div>
                </div>
                <div class="small font-weight-bold mb-1">Trims and Accessories Details</div>
                <table class="table table-bordered table-sm mb-0 cs-trims">
                    <tbody>
                        @foreach(\ME\SflInventory\Support\ClosingSummaryTrims::rows() as $row)
                            <tr>
                                @foreach($row as $cell)
                                    @if($cell)
                                        <th>{{ $cell[1] }}</th>
                                        <td><input type="text" name="{{ $n }}[trims][{{ $cell[0] }}]" class="form-control form-control-sm" maxlength="50" value="{{ $p['trims'][$cell[0]] ?? '' }}"></td>
                                    @else
                                        <th></th><td></td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
