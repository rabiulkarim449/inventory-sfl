<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Model;

/** One product block of a Style Closing Summary. */
class ClosingSummaryProduct extends Model
{
    protected $table = 'inv_closing_summary_products';

    protected $fillable = ['closing_summary_id', 'sequence', 'product', 'bom_qty', 'ship_qty', 'remarks', 'trims'];

    protected $casts = ['trims' => 'array'];

    /** Ship − BOM: minus = short, plus = excess; null until both are given. */
    public function shortExcess(): ?float
    {
        return $this->bom_qty === null || $this->ship_qty === null ? null : (float) $this->ship_qty - (float) $this->bom_qty;
    }

    public function trim(string $key): string
    {
        return (string) ($this->trims[$key] ?? '');
    }
}
