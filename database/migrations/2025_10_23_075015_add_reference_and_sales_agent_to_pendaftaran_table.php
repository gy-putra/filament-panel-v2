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
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->enum('reference', ['Social Media', 'Walk In', 'Agent'])->nullable()->after('id');
            $table->unsignedBigInteger('sales_agent_id')->nullable()->after('reference');
            
            $table->foreign('sales_agent_id')->references('id')->on('sales_agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->dropForeign(['sales_agent_id']);
            $table->dropColumn(['reference', 'sales_agent_id']);
        });
    }
};
