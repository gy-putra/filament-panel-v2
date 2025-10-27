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
        Schema::table('paket_keberangkatan', function (Blueprint $table) {
            $table->foreignId('umrah_program_id')
                  ->nullable()
                  ->after('program_title')
                  ->constrained('umrah_programs')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paket_keberangkatan', function (Blueprint $table) {
            $table->dropForeign(['umrah_program_id']);
            $table->dropColumn('umrah_program_id');
        });
    }
};
