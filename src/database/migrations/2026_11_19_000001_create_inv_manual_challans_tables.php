<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual Delivery Challan — typed by hand, not linked to any order, buyer or
 * stock. Four types (see ManualChallanTypes) share these two tables and one
 * daily Challan No series (ddmmyy + 3-digit serial).
 *
 * The line table carries every column any type prints; a type uses only its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_manual_challans', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('challan_no', 20)->unique();
            $table->date('challan_date')->index();
            $table->string('to_name');
            $table->string('to_address')->nullable();
            $table->string('to_contact', 100)->nullable();
            // Green (optional) columns of this challan shown / printed.
            $table->boolean('show_optional')->default(false);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inv_manual_challan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manual_challan_id')->index();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('description')->nullable();
            $table->string('po_no', 100)->nullable();
            $table->string('style', 100)->nullable();
            $table->string('item_code', 100)->nullable();
            $table->string('color', 100)->nullable();
            $table->string('size', 50)->nullable();
            // Size-wise qty, e.g. {"S": 1, "M": 3} — Shipment & Sizeset.
            $table->json('sizes')->nullable();
            $table->decimal('buyer_sample_qty', 12, 2)->nullable();
            $table->decimal('sfl_sample_qty', 12, 2)->nullable();
            $table->decimal('qty', 12, 2)->default(0);
            $table->string('unit', 30)->nullable();
            $table->string('hanger_carton', 50)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('manual_challan_id')->references('id')->on('inv_manual_challans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_manual_challan_items');
        Schema::dropIfExists('inv_manual_challans');
    }
};
