{{-- Inventory & Return form table (no green columns). props: lines (form.blade.php). --}}
<div class="table-responsive">
    <table class="table table-bordered table-sm mc-lines">
        <thead>
            <tr>
                <th style="width:40px">SL</th>
                <th>PO No / Challan No</th>
                <th>Description of Item</th>
                <th>Item Code</th>
                <th>Color</th>
                <th>Qty</th>
                <th>Unit of Measurement</th>
                <th>Remarks</th>
                <th style="width:36px"></th>
            </tr>
        </thead>
        <tbody id="mcRows">
            @foreach($lines as $i => $line)
                @include('sfl-inventory::admin.manual-challans.partials.inventory_return-row', ['index' => $i, 'line' => $line])
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-weight-bold">
                <td colspan="5" class="text-right">Total</td>
                <td class="text-right" id="mcGrandTotal"></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</div>
<template id="mcRowTemplate">
    @include('sfl-inventory::admin.manual-challans.partials.inventory_return-row', ['index' => '__INDEX__', 'line' => []])
</template>
