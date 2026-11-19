<?php

namespace ME\SflInventory\Support;

/**
 * The Manual Delivery Challan types. Each type has its own menu, list, form
 * and print layout; all share one Challan No series.
 *
 * To add a type: add it here (and its print partial), and a menu line in
 * Config/sidebar.php. No migration — the line table holds every column.
 */
class ManualChallanTypes
{
    /** Sizes of the Shipment & Sizeset size-wise columns, in print order. */
    public const SIZES = ['XS', 'S', 'M', 'XM', 'L', 'XL', '2XL', '3XL'];

    public const TYPES = [
        'shipment' => [
            'title' => 'Delivery Challan for Shipment & Sizeset',
            'menu' => 'Shipment & Sizeset',
            // Green columns: each size and Hanger / Carton, ticked one by one on the form.
            'optional' => [...self::SIZES, 'hanger_carton'],
            'sizes' => self::SIZES,
        ],
        'inventory_return' => [
            'title' => 'Delivery Challan for Inventory & Return',
            'menu' => 'Inventory & Return',
        ],
        'marketing' => [
            'title' => 'Delivery Challan for Marketing & Development Sample',
            'menu' => 'Marketing & Development Sample',
        ],
        'pp_sample' => [
            'title' => 'Delivery Challan for PP Sample',
            'menu' => 'PP Sample',
            // Total Qty = Sample of Buyer + PP Sample from SFL.
            'total_of' => ['buyer_sample_qty', 'sfl_sample_qty'],
        ],
    ];

    /** Green columns a type allows (empty for a type without any). */
    public static function optionalColumns(string $type): array
    {
        return self::get($type)['optional'] ?? [];
    }

    public static function all(): array
    {
        return self::TYPES;
    }

    public static function exists(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function get(string $type): array
    {
        return self::TYPES[$type] ?? abort(404);
    }
}
