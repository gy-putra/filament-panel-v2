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
        Schema::create('tabungan_target', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tabungan_id')->constrained('tabungan')->cascadeOnDelete()->unique();
            $table->decimal('target_nominal', 15, 2);
            $table->date('deadline')->nullable();
            $table->foreignId('paket_target_id')->nullable()->constrained('paket_keberangkatan')->nullOnDelete();
            $table->json('rencana_bulanan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabungan_target');
    }
};
