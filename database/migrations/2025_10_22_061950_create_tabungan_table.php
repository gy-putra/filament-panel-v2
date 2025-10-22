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
        Schema::create('tabungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jamaah_id')->constrained('jamaah')->unique();
            $table->string('nomor_rekening', 30)->unique();
            $table->string('nama_ibu_kandung', 150);
            $table->enum('nama_bank', ['BSI', 'BJB']);
            $table->date('tanggal_buka_rekening');
            $table->decimal('saldo_tersedia', 15, 2)->default(0.00);
            $table->decimal('saldo_terkunci', 15, 2)->default(0.00);
            $table->enum('status', ['aktif', 'non_aktif'])->default('aktif');
            $table->date('dibuka_pada')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'tanggal_buka_rekening']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabungan');
    }
};
