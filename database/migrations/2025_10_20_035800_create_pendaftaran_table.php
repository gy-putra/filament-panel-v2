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
        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pendaftaran', 20)->unique();
            $table->foreignId('paket_keberangkatan_id')->constrained('paket_keberangkatan')->onDelete('cascade');
            $table->foreignId('jamaah_id')->constrained('jamaah')->onDelete('cascade');
            $table->date('tgl_daftar');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['paket_keberangkatan_id', 'jamaah_id']);
            $table->index('tgl_daftar');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftaran');
    }
};