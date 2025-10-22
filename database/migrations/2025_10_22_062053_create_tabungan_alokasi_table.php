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
        Schema::create('tabungan_alokasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tabungan_id')->constrained('tabungan')->cascadeOnDelete();
            $table->foreignId('pendaftaran_id')->nullable()->constrained('pendaftaran')->nullOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable(); // Foreign key constraint removed - invoice table doesn't exist yet
            $table->dateTime('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['tabungan_id', 'status', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabungan_alokasi');
    }
};
