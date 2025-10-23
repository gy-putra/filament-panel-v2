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
        Schema::table('jamaah', function (Blueprint $table) {
            $table->string('no_paspor', 50)->nullable()->after('no_bpjs');
            $table->string('kota_paspor', 100)->nullable()->after('no_paspor');
            $table->date('tgl_terbit_paspor')->nullable()->after('kota_paspor');
            $table->date('tgl_expired_paspor')->nullable()->after('tgl_terbit_paspor');
            $table->string('foto_jamaah')->nullable()->after('tgl_expired_paspor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jamaah', function (Blueprint $table) {
            $table->dropColumn([
                'no_paspor',
                'kota_paspor',
                'tgl_terbit_paspor',
                'tgl_expired_paspor',
                'foto_jamaah'
            ]);
        });
    }
};
