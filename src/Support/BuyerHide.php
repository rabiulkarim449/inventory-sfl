<?php

namespace ME\SflInventory\Support;

use Illuminate\Support\Facades\DB;

/**
 * Reports only: a role may be barred from one buyer type (Export or Local) — two
 * ticks on the Buyers row of the Merchandising permissions. The rule itself lives in
 * the Merchandising package; this class only applies it to store documents.
 *
 * A document is hidden when its Merchandising buyer is hidden, or when its own
 * (inventory) buyer has the same name as a hidden Merchandising buyer. Documents
 * without a buyer — general purchase, stationery — always show.
 *
 * Without the Merchandising package nothing is hidden.
 */
class BuyerHide
{
    private const RULE = '\ME\MerchandisingSfl\Support\BuyerVisibility';

    private static array $inv = [];

    /** Hidden Merchandising buyer ids for the logged-in user. */
    public static function merIds(): array
    {
        return class_exists(self::RULE) ? (self::RULE)::hiddenBuyerIds() : [];
    }

    /** Inventory buyers standing for a hidden Merchandising buyer (matched by name). */
    public static function invIds(): array
    {
        $mer = self::merIds();
        if ($mer === []) {
            return [];
        }
        $key = implode(',', $mer);
        if (! isset(self::$inv[$key])) {
            $names = DB::table('msfl_buyers')->whereIn('id', $mer)->pluck('name')->map(fn ($n) => mb_strtolower(trim((string) $n)))->filter()->all();
            self::$inv[$key] = $names === [] ? [] : DB::table('inv_buyers')->whereIn(DB::raw('LOWER(TRIM(name))'), $names)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return self::$inv[$key];
    }

    public static function active(): bool
    {
        return self::merIds() !== [];
    }

    /**
     * Drop the hidden buyers' documents from a report query.
     * $table: the document table or its alias. $merColumn: false for tables that
     * only carry the inventory buyer_id (gate passes, shipments).
     */
    public static function apply($query, string $table, bool $merColumn = true)
    {
        $mer = self::merIds();
        if ($mer === []) {
            return $query;
        }
        $inv = self::invIds();
        if ($merColumn) {
            $query->where(fn ($q) => $q->whereNull("{$table}.msfl_buyer_id")->orWhereNotIn("{$table}.msfl_buyer_id", $mer));
        }
        if ($inv !== []) {
            $query->where(fn ($q) => $q->whereNull("{$table}.buyer_id")->orWhereNotIn("{$table}.buyer_id", $inv));
        }

        return $query;
    }

    /** Buyer dropdown of a report: without the hidden buyers. */
    public static function buyers($query)
    {
        $inv = self::invIds();

        return $inv === [] ? $query : $query->whereNotIn('id', $inv);
    }
}
