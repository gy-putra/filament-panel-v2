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
        Schema::table('hotel', function (Blueprint $table) {
            $table->foreignId('paket_keberangkatan_id')->nullable()->after('id')->constrained('paket_keberangkatan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel', function (Blueprint $table) {
            $table->dropForeign(['paket_keberangkatan_id']);
            $table->dropColumn('paket_keberangkatan_id');
        });
    }
};
