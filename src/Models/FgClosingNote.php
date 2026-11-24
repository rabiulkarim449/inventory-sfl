<?php

namespace ME\SflInventory\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;

/** Typed Remarks / Comments of a Finished Goods Style Closing Summary (one per order + PO no). */
class FgClosingNote extends Model
{
    use HasAudit;

    protected $table = 'inv_fg_closing_notes';

    protected $fillable = ['msfl_order_id', 'po_no', 'remarks', 'comments', 'updated_by'];

    protected $casts = ['remarks' => 'array'];
}
