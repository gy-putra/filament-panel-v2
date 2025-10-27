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
        // Fix TabunganTarget table - change from nullOnDelete to cascade
        Schema::table('tabungan_target', function (Blueprint $table) {
            $table->dropForeign(['paket_target_id']);
            $table->foreignId('paket_target_id')->nullable()->change();
            $table->foreign('paket_target_id')->references('id')->on('paket_keberangkatan')->onDelete('cascade');
        });

        // Fix Hotels table - change from 'set null' to cascade
        Schema::table('hotel', function (Blueprint $table) {
            $table->dropForeign(['paket_keberangkatan_id']);
            $table->foreignId('paket_keberangkatan_id')->nullable()->change();
            $table->foreign('paket_keberangkatan_id')->references('id')->on('paket_keberangkatan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert TabunganTarget table back to nullOnDelete
        Schema::table('tabungan_target', function (Blueprint $table) {
            $table->dropForeign(['paket_target_id']);
            $table->foreignId('paket_target_id')->nullable()->change();
            $table->foreign('paket_target_id')->references('id')->on('paket_keberangkatan')->nullOnDelete();
        });

        // Revert Hotels table back to 'set null'
        Schema::table('hotel', function (Blueprint $table) {
            $table->dropForeign(['paket_keberangkatan_id']);
            $table->foreignId('paket_keberangkatan_id')->nullable()->change();
            $table->foreign('paket_keberangkatan_id')->references('id')->on('paket_keberangkatan')->onDelete('set null');
        });
    }
};
