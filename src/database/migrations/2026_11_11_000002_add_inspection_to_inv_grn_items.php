<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inspection of a received line, recorded after the goods are in (GRN show page): passed / partial /
 * failed (+ remark). Only a record — stock already moved on receive and is not touched by it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_grn_items', function (Blueprint $table) {
            $table->string('inspection_status', 20)->nullable()->after('rejected_qty'); // null = not inspected yet
            $table->string('inspection_remarks')->nullable()->after('inspection_status');
            $table->unsignedBigInteger('inspected_by')->nullable()->after('inspection_remarks');
            $table->timestamp('inspected_at')->nullable()->after('inspected_by');
        });
    }

    public function down(): void
    {
        Schema::table('inv_grn_items', function (Blueprint $table) {
            $table->dropColumn(['inspection_status', 'inspection_remarks', 'inspected_by', 'inspected_at']);
        });
    }
};
