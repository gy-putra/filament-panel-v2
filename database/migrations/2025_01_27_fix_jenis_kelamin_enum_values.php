<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to match new enum values
        DB::table('jamaah')->where('jenis_kelamin', 'Laki-laki')->update(['jenis_kelamin' => 'L']);
        // 'P' values remain the same
        
        // Then modify the enum column to use ['L', 'P'] instead of ['Laki-laki', 'P']
        Schema::table('jamaah', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['L', 'P'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update data back to original format
        DB::table('jamaah')->where('jenis_kelamin', 'L')->update(['jenis_kelamin' => 'Laki-laki']);
        
        // Then revert the enum column back to original values
        Schema::table('jamaah', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['Laki-laki', 'P'])->change();
        });
    }
};