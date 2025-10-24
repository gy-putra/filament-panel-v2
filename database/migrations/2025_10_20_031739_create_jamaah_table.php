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
        Schema::create('jamaah', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identity fields
            $table->string('kode_jamaah', 20)->unique();
            $table->string('nama_lengkap', 150);
            $table->string('nama_ayah', 150);
            $table->enum('jenis_kelamin', ['Laki-laki', 'P']);
            $table->date('tgl_lahir');
            $table->string('tempat_lahir', 100)->nullable();
            $table->enum('pendidikan_terakhir', ['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3', 'Lainnya']);

            // Nationality & IDs
            $table->string('kewarganegaraan', 64)->default('Indonesia');
            $table->string('no_ktp', 32)->unique()->nullable();
            $table->string('no_bpjs', 30)->nullable();

            // Address & Contact
            $table->text('alamat');
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('negara', 100)->default('Indonesia');
            $table->string('no_hp', 32);
            $table->string('email', 150)->nullable();

            // Status & Work
            $table->enum('status_pernikahan', ['Single', 'Married', 'Widowed', 'Divorced']);
            $table->string('pekerjaan', 100)->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for search performance
            $table->index(['nama_lengkap']);
            $table->index(['no_hp']);
            $table->index(['email']);
            $table->index(['no_ktp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jamaah');
    }
};