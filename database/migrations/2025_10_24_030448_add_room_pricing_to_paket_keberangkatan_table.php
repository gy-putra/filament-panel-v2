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
            $table->decimal('harga_quad', 15, 2)->nullable()->after('harga_paket');
            $table->decimal('harga_triple', 15, 2)->nullable()->after('harga_quad');
            $table->decimal('harga_double', 15, 2)->nullable()->after('harga_triple');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paket_keberangkatan', function (Blueprint $table) {
            $table->dropColumn(['harga_quad', 'harga_triple', 'harga_double']);
        });
    }
};
