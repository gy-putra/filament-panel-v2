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
        Schema::table('sales_agents', function (Blueprint $table) {
            $table->string('place_of_birth', 150)->after('birth_date')->comment('Agent place of birth');
            $table->string('account_name', 150)->after('account_number')->comment('Bank account holder name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_agents', function (Blueprint $table) {
            $table->dropColumn(['place_of_birth', 'account_name']);
        });
    }
};
