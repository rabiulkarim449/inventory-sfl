<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which line of the Style Closing Summary's "Trims and Accessories Details" an
 * item is counted on (a key of ClosingSummaryTrims, e.g. "poly", "carton").
 * Blank = not on the sheet. Set once per item on the item form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->string('closing_summary_row', 30)->nullable()->after('item_type');
        });
    }

    public function down(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->dropColumn('closing_summary_row');
        });
    }
};
