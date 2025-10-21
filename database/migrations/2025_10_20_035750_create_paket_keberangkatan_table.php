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
        Schema::create('paket_keberangkatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_paket', 20)->unique();
            $table->string('nama_paket', 200);
            $table->date('tgl_keberangkatan');
            $table->date('tgl_kepulangan');
            $table->integer('kuota_total');
            $table->integer('kuota_terisi')->default(0);
            $table->decimal('harga_paket', 15, 2);
            $table->enum('status', ['draft', 'open', 'closed', 'cancelled'])->default('draft');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tgl_keberangkatan');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paket_keberangkatan');
    }
};