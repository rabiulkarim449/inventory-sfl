<?php

namespace ME\SflInventory\Support;

/**
 * The "Trims and Accessories Details" grid of the Style Closing Summary, in the
 * three columns of the client's sheet (top to bottom). Both product blocks of
 * that sheet are merged into one list (Label → Main / Size / Care Label).
 */
class ClosingSummaryTrims
{
    public const COLUMNS = [
        [
            'fabrics' => 'Fabrics',
            'cutting_parts' => 'Cutting Parts',
            'emb_print_parts' => 'Emb./Print Parts',
            'main_label' => 'Main Label',
            'size_label' => 'Size Label',
            'care_label' => 'Care Label',
            'drawstring' => 'Drawstring',
            'draw_cord' => 'Draw Cord',
            'button' => 'Button',
        ],
        [
            'hangtag' => 'Hangtag',
            'barcode' => 'Barcode',
            'silica' => 'Silica',
            'poly' => 'Poly',
            'collar_stand' => 'Collar Stand',
            'clip_safetypin' => 'Clip/Safetypin',
            'gumtape' => 'Gumtape',
        ],
        [
            'carton' => 'Carton',
            'others' => 'Others',
        ],
    ];

    /** Every trim key => label. */
    public static function all(): array
    {
        return array_merge(...self::COLUMNS);
    }

    /** Rows of the grid: each row is [ [key, label] | null, ... ] across the three columns. */
    public static function rows(): array
    {
        $cols = array_map(fn ($c) => array_map(null, array_keys($c), array_values($c)), self::COLUMNS);
        $height = max(array_map('count', $cols));
        $rows = [];
        for ($i = 0; $i < $height; $i++) {
            $rows[] = array_map(fn ($c) => $c[$i] ?? null, $cols);
        }

        return $rows;
    }
}
