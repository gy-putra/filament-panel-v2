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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_booking_id')->constrained('hotel_bookings')->onDelete('cascade');
            $table->string('nomor_kamar', 20);
            $table->enum('tipe_kamar', ['single', 'double', 'triple', 'quad']);
            $table->integer('kapasitas');
            $table->enum('gender_preference', ['Laki-laki', 'P', 'mixed'])->default('mixed');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            // Indexes
            $table->unique(['hotel_booking_id', 'nomor_kamar']);
            $table->index('tipe_kamar');
            $table->index('gender_preference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};