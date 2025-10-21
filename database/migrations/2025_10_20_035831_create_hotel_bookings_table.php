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
        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_keberangkatan_id')->constrained('paket_keberangkatan')->onDelete('cascade');
            $table->foreignId('hotel_id')->constrained('hotel')->onDelete('cascade');
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('jumlah_malam');
            $table->timestamps();

            // Indexes
            $table->unique(['paket_keberangkatan_id', 'hotel_id']);
            $table->index('check_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
    }
};