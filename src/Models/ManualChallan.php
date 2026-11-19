<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use ME\SflInventory\Support\ManualChallanTypes;

/**
 * Manual Delivery Challan — typed by hand, no order / buyer / stock link.
 * Pending until approved; the Challan No is given once, on submit, and never changes.
 */
class ManualChallan extends Model
{
    use HasAudit;
    use SoftDeletes;

    public const STATUSES = [
        'pending' => ['Pending', 'warning'],
        'approved' => ['Approved', 'success'],
        'rejected' => ['Rejected', 'danger'],
    ];

    protected $table = 'inv_manual_challans';

    protected $fillable = [
        'type', 'challan_no', 'challan_date', 'to_name', 'to_address', 'to_contact', 'optional_columns',
        'status', 'approved_by', 'approved_at', 'approval_remarks', 'created_by',
    ];

    protected $casts = ['challan_date' => 'date', 'approved_at' => 'datetime', 'optional_columns' => 'array'];

    /**
     * Next Challan No for a date: ddmmyy + 3-digit serial, e.g. 191126001.
     * One series for every type; restarts each day. Deleted challans keep
     * their number, so no number is ever given twice. Call inside a transaction.
     */
    public static function nextNumber(\DateTimeInterface $date): string
    {
        $prefix = $date->format('dmy');
        $last = static::withTrashed()->where('challan_no', 'like', $prefix . '%')->lockForUpdate()
            ->max(DB::raw('CAST(SUBSTRING(challan_no, ' . (strlen($prefix) + 1) . ') AS UNSIGNED)'));

        return $prefix . str_pad((string) (((int) $last) + 1), 3, '0', STR_PAD_LEFT);
    }

    public function typeInfo(): array
    {
        return ManualChallanTypes::get($this->type);
    }

    /** A green (optional) column ticked on this challan. */
    public function optionalOn(string $column): bool
    {
        return in_array($column, $this->optional_columns ?? [], true);
    }

    /** The sizes ticked on this challan, in print order. */
    public function optionalSizes(): array
    {
        return array_values(array_filter(ManualChallanTypes::SIZES, fn ($s) => $this->optionalOn($s)));
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function totalQty(): float
    {
        return (float) $this->items->sum('qty');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ManualChallanItem::class)->orderBy('sequence');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
