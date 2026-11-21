<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Model;

/** One typed line of a Manual Delivery Challan. */
class ManualChallanItem extends Model
{
    protected $table = 'inv_manual_challan_items';

    protected $fillable = [
        'manual_challan_id', 'sequence', 'description', 'po_no', 'style', 'item_code', 'color', 'size', 'sizes',
        'buyer_sample_qty', 'sfl_sample_qty', 'qty', 'unit', 'hanger', 'carton', 'remarks',
    ];

    protected $casts = ['sizes' => 'array'];

    public function sizeQty(string $size): float
    {
        return (float) ($this->sizes[$size] ?? 0);
    }
}
