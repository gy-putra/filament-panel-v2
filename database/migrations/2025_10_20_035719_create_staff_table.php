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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('no_hp', 20);
            $table->string('email', 100)->nullable();
            $table->enum('tipe_staff', ['muthowif', 'muthowifah', 'lapangan', 'dokumen', 'medis', 'lainnya']);
            $table->timestamps();

            // Indexes
            $table->unique('email');
            $table->index('no_hp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};