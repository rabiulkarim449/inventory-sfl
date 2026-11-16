<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional Buyer → Style → PO on a purchase requisition (Merchandising), so what is bought for an order can
 * be traced: its Store Order (purchase_requisition_id) and that order's GRNs inherit it. Same soft
 * references (no foreign key) as the GRN / requisition / issue columns; empty for general purchases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_purchase_requisitions', function (Blueprint $table) {
            $table->unsignedBigInteger('msfl_buyer_id')->nullable()->after('department_id')->index();
            $table->unsignedBigInteger('msfl_style_id')->nullable()->after('msfl_buyer_id')->index();
            $table->unsignedBigInteger('msfl_order_po_id')->nullable()->after('msfl_style_id')->index();
            $table->string('style', 150)->nullable()->after('msfl_order_po_id'); // style no (also an older Inventory-only style)
        });
    }

    public function down(): void
    {
        Schema::table('inv_purchase_requisitions', function (Blueprint $table) {
            $table->dropIndex(['msfl_buyer_id']);
            $table->dropIndex(['msfl_style_id']);
            $table->dropIndex(['msfl_order_po_id']);
            $table->dropColumn(['msfl_buyer_id', 'msfl_style_id', 'msfl_order_po_id', 'style']);
        });
    }
};
