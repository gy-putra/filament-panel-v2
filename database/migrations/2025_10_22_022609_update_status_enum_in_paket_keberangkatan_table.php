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
        // For MySQL, we need to use raw SQL to modify enum values
        DB::statement("ALTER TABLE paket_keberangkatan MODIFY COLUMN status ENUM('draft', 'open', 'published', 'closed', 'cancelled') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE paket_keberangkatan MODIFY COLUMN status ENUM('draft', 'open', 'closed', 'cancelled') DEFAULT 'draft'");
    }
};
