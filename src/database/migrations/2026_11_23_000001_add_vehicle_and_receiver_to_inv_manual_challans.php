<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Manual Challan: Vehicle No (by the date) and Receiver Name / Contact (under the receiver's signature). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_manual_challans', function (Blueprint $table) {
            $table->string('vehicle_no', 100)->nullable()->after('to_contact');
            $table->string('receiver_name')->nullable()->after('vehicle_no');
            $table->string('receiver_contact', 100)->nullable()->after('receiver_name');
        });
    }

    public function down(): void
    {
        Schema::table('inv_manual_challans', function (Blueprint $table) {
            $table->dropColumn(['vehicle_no', 'receiver_name', 'receiver_contact']);
        });
    }
};
