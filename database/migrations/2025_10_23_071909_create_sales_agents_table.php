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
        Schema::create('sales_agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_code', 20)->unique()->comment('Unique agent code, e.g., AG-2025-001');
            $table->string('name', 150)->comment('Agent full name');
            $table->date('birth_date')->comment('Agent birth date');
            $table->text('address')->comment('Agent address');
            $table->string('phone_number', 32)->comment('Agent phone number');
            $table->enum('type', ['internal', 'external'])->comment('Agent type: internal (employee) or external');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Agent current status');
            
            // Account Information for commission management
            $table->string('bank_name', 150)->comment('Bank name for commission payments');
            $table->string('account_number', 50)->nullable()->comment('Bank account number');
            $table->string('agency_name', 150)->nullable()->comment('Agency name if external agent');
            $table->date('join_on')->comment('Agent joining date');
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('Admin who created the record');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_agents');
    }
};
