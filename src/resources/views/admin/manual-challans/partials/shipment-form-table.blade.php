{{-- Shipment & Sizeset form table. props: lines, on, sizes, greenHeader (form.blade.php). --}}
                <p class="small text-muted mb-1"><span class="mc-optional px-1">Green</span> columns are optional: tick one in the header to fill it in. Only ticked columns are printed.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mc-lines">
                        <thead>
                            <tr>
                                <th style="width:40px">SL</th>
                                <th>Description of Item</th>
                                <th>PO Number</th>
                                <th>Style</th>
                                <th>Color</th>
                                @foreach($sizes as $size)
                                    <th class="mc-optional">{!! $greenHeader($size, $size) !!}</th>
                                @endforeach
                                <th>Total Qty</th>
                                <th>Unit</th>
                                <th class="mc-optional">{!! $greenHeader('hanger_carton', 'Hanger / Carton') !!}</th>
                                <th>Remarks</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="mcRows">
                            @foreach($lines as $i => $line)
                                @include('sfl-inventory::admin.manual-challans.partials.shipment-row', ['index' => $i, 'line' => $line, 'on' => $on])
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="5" class="text-right">Total</td>
                                @foreach($sizes as $size)
                                    <td class="text-right" data-size-total="{{ $size }}"></td>
                                @endforeach
                                <td class="text-right" id="mcGrandTotal"></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <template id="mcRowTemplate">
                    @include('sfl-inventory::admin.manual-challans.partials.shipment-row', ['index' => '__INDEX__', 'line' => [], 'on' => []])
                </template>
