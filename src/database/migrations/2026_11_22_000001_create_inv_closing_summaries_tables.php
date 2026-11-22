<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Style Closing Summary (Manual Challan section): one sheet per closed style / PO.
 * Typed by hand; no number. Each product block holds BOM / Ship qty and the
 * trims & accessories left over, keyed as in ClosingSummaryTrims.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_closing_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');                 // set by the system on save
            $table->string('factory_name')->nullable();  // typed; the form starts with the company's own
            $table->string('factory_location')->nullable();
            $table->string('po_no', 100)->nullable();
            $table->string('logo')->nullable();          // path on the "public" disk
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('report_date');
        });

        Schema::create('inv_closing_summary_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('closing_summary_id')->index();
            $table->unsignedInteger('sequence')->default(1);
            $table->text('product')->nullable();         // name, style, PO — as typed, one per line
            $table->decimal('bom_qty', 15, 2)->nullable();
            $table->decimal('ship_qty', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->json('trims')->nullable();           // {trim key: value as typed}
            $table->timestamps();
            $table->foreign('closing_summary_id')->references('id')->on('inv_closing_summaries')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_closing_summary_products');
        Schema::dropIfExists('inv_closing_summaries');
    }
};
