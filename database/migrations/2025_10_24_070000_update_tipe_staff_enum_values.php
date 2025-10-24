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
        // Update the enum values for tipe_staff to include Marketing, Finance, and Operational
        DB::statement("ALTER TABLE staff MODIFY COLUMN tipe_staff ENUM('muthowif', 'muthowifah', 'lapangan', 'dokumen', 'medis', 'lainnya', 'Marketing', 'Finance', 'Operational')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE staff MODIFY COLUMN tipe_staff ENUM('muthowif', 'muthowifah', 'lapangan', 'dokumen', 'medis', 'lainnya')");
    }
};