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
        Schema::create('flight_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_keberangkatan_id')->constrained('paket_keberangkatan')->onDelete('cascade');
            $table->foreignId('maskapai_id')->constrained('maskapai')->onDelete('cascade');
            $table->enum('tipe', ['keberangkatan', 'kepulangan']);
            $table->string('nomor_penerbangan', 20);
            $table->string('asal', 100);
            $table->string('tujuan', 100);
            $table->dateTime('waktu_berangkat');
            $table->dateTime('waktu_tiba');
            $table->timestamps();

            // Indexes
            $table->index(['paket_keberangkatan_id', 'tipe']);
            $table->index('waktu_berangkat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_segments');
    }
};