<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration reverts the cascade deletion constraints for TabunganTarget
     * and Hotel tables to use SET NULL instead of CASCADE, since these belong
     * to different navigation groups and should not be deleted when a 
     * PaketKeberangkatan is deleted.
     */
    public function up(): void
    {
        // Revert TabunganTarget table - change from cascade back to SET NULL
        // TabunganTarget belongs to "Tabungan Management", not "Departure Management"
        // Only modify if the table exists
        if (Schema::hasTable('tabungan_target')) {
            Schema::table('tabungan_target', function (Blueprint $table) {
                $table->dropForeign(['paket_target_id']);
                $table->foreign('paket_target_id')
                      ->references('id')
                      ->on('paket_keberangkatan')
                      ->onDelete('set null');
            });
        }

        // Revert Hotel table - change from cascade back to SET NULL
        // Hotel is master data that should be preserved
        // Only modify if the table exists
        if (Schema::hasTable('hotel')) {
            Schema::table('hotel', function (Blueprint $table) {
                $table->dropForeign(['paket_keberangkatan_id']);
                $table->foreign('paket_keberangkatan_id')
                      ->references('id')
                      ->on('paket_keberangkatan')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore cascade deletion for TabunganTarget
        // Only modify if the table exists
        if (Schema::hasTable('tabungan_target')) {
            Schema::table('tabungan_target', function (Blueprint $table) {
                $table->dropForeign(['paket_target_id']);
                $table->foreign('paket_target_id')
                      ->references('id')
                      ->on('paket_keberangkatan')
                      ->onDelete('cascade');
            });
        }

        // Restore cascade deletion for Hotel
        // Only modify if the table exists
        if (Schema::hasTable('hotel')) {
            Schema::table('hotel', function (Blueprint $table) {
                $table->dropForeign(['paket_keberangkatan_id']);
                $table->foreign('paket_keberangkatan_id')
                      ->references('id')
                      ->on('paket_keberangkatan')
                      ->onDelete('cascade');
            });
        }
    }
};