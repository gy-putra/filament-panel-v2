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
        Schema::create('tabungan_setoran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tabungan_id')->constrained('tabungan')->cascadeOnDelete();
            $table->dateTime('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->enum('metode', ['transfer', 'tunai', 'gateway']);
            $table->string('bukti_path', 255)->nullable();
            $table->enum('status_verifikasi', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['tabungan_id', 'status_verifikasi', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabungan_setoran');
    }
};
