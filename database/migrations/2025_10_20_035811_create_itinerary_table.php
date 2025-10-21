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
        Schema::create('itinerary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_keberangkatan_id')->constrained('paket_keberangkatan')->onDelete('cascade');
            $table->integer('hari_ke');
            $table->date('tanggal');
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['paket_keberangkatan_id', 'hari_ke']);
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary');
    }
};