<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hotel_bookings', function (Blueprint $table) {
            $table->integer('jumlah_kamar')->default(1)->after('jumlah_malam');
            $table->string('status_booking')->default('pending')->after('jumlah_kamar');
            $table->string('nomor_booking')->nullable()->after('status_booking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_bookings', function (Blueprint $table) {
            $table->dropColumn(['jumlah_kamar', 'status_booking', 'nomor_booking']);
        });
    }
};