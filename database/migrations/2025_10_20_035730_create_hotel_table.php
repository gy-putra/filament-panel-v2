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
        Schema::create('hotel', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('kota', 100);
            $table->timestamps();

            // Indexes
            $table->unique(['nama', 'kota']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel');
    }
};