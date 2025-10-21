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
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('gender_preference');
        });
        
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('gender_preference', ['laki-laki', 'perempuan', 'mixed'])->default('mixed')->after('kapasitas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('gender_preference');
        });
        
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('gender_preference', ['L', 'P', 'mixed'])->default('mixed')->after('kapasitas');
        });
    }
};