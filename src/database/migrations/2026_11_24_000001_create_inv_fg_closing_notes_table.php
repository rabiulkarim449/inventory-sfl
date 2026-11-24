<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finished Goods Style Closing Summary: everything on the sheet is filled by the
 * system except Remarks (per style / colour line) and Comments, typed here.
 * One row per order + PO number — the same grouping the sheet uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_fg_closing_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('msfl_order_id');
            $table->string('po_no', 100);
            $table->json('remarks')->nullable();          // {msfl order PO id: text}
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['msfl_order_id', 'po_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_fg_closing_notes');
    }
};
