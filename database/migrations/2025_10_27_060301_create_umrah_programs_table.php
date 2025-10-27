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
        Schema::create('umrah_programs', function (Blueprint $table) {
            $table->id();
            $table->string('program_code', 20)->unique();
            $table->string('program_name', 150);
            $table->timestamps();

            // Indexes
            $table->index('program_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umrah_programs');
    }
};
