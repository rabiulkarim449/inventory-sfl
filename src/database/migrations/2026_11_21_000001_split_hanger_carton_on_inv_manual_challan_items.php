<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manual Challan (Shipment & Sizeset): the one "Hanger / Carton" text column
 * becomes two number columns, Hanger and Carton, each ticked on its own.
 *
 * Existing lines: a number in hanger_carton moves to Hanger; any other text
 * is kept at the front of Remarks. Challans that had the column ticked get
 * Hanger ticked. The old column stays (unused) so nothing is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_manual_challan_items', function (Blueprint $table) {
            $table->decimal('hanger', 12, 2)->nullable()->after('unit');
            $table->decimal('carton', 12, 2)->nullable()->after('hanger');
        });

        foreach (DB::table('inv_manual_challan_items')->whereNotNull('hanger_carton')->where('hanger_carton', '<>', '')->get(['id', 'hanger_carton', 'remarks']) as $line) {
            $text = trim($line->hanger_carton);
            DB::table('inv_manual_challan_items')->where('id', $line->id)->update(is_numeric($text)
                ? ['hanger' => $text]
                : ['remarks' => mb_substr(trim('Hanger/Carton: ' . $text . ' ' . $line->remarks), 0, 255)]);
        }

        foreach (DB::table('inv_manual_challans')->whereNotNull('optional_columns')->get(['id', 'optional_columns']) as $challan) {
            $cols = json_decode($challan->optional_columns, true) ?: [];
            if (in_array('hanger_carton', $cols, true)) {
                $cols = array_values(array_unique(array_map(fn ($c) => $c === 'hanger_carton' ? 'hanger' : $c, $cols)));
                DB::table('inv_manual_challans')->where('id', $challan->id)->update(['optional_columns' => json_encode($cols)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('inv_manual_challan_items', function (Blueprint $table) {
            $table->dropColumn(['hanger', 'carton']);
        });
    }
};
