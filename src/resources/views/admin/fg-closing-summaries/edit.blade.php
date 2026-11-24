@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Style Closing Summary — Edit') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
            <h4 class="mb-0">Style Closing Summary — PO {{ $summary->po_no }}</h4>
            <a href="{{ route('inventory.fg-closing-summaries.show', $orderPo) }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-3">Everything else on the sheet is filled by the system. Only Remarks and Comments are typed here.</div>

            <form method="POST" action="{{ route('inventory.fg-closing-summaries.update', $orderPo) }}">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead>
                            <tr><th style="width:30%">Product</th><th class="text-right" style="width:10%">BOM Qty</th><th class="text-right" style="width:10%">Ship Qty</th><th>Remarks</th></tr>
                        </thead>
                        <tbody>
                            @foreach($summary->products as $p)
                                <tr>
                                    <td>{!! nl2br(e($p->product)) !!}</td>
                                    <td class="text-right">{{ inv_qty($p->bom_qty) }}</td>
                                    <td class="text-right">{{ inv_qty($p->ship_qty) }}</td>
                                    <td>
                                        <textarea name="remarks[{{ $p->id }}]" rows="3" class="form-control form-control-sm @error('remarks.' . $p->id) is-invalid @enderror" maxlength="2000">{{ old('remarks.' . $p->id, $p->remarks) }}</textarea>
                                        @error('remarks.' . $p->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Comments</label>
                    <textarea name="comments" rows="3" class="form-control form-control-sm @error('comments') is-invalid @enderror" maxlength="5000">{{ old('comments', $summary->comments) }}</textarea>
                    @error('comments')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-sm ui-act ui-act-save" title="Save"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                    <a href="{{ route('inventory.fg-closing-summaries.show', $orderPo) }}" class="btn btn-sm ui-act ui-act-cancel" title="Cancel"><i class="fa-solid fa-ban"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
