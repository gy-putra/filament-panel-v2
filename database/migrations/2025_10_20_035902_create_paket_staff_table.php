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
        Schema::create('paket_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_keberangkatan_id')->constrained('paket_keberangkatan')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->unique(['paket_keberangkatan_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paket_staff');
    }
};