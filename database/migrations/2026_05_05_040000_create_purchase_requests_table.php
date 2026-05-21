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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');
            $table->string('requester_dept_code')->nullable();
            $table->integer('user_sequence'); // Untuk Auto-generate No. PP
            $table->string('pp_number')->unique();
            $table->enum('expense_type', ['CAPEX', 'OPEX']);
            $table->text('remarks')->nullable();

            // Checklist Pembelian Terkait (Keputusan final user dari AI Suggestion)
            $table->boolean('is_related_hse')->default(false);
            $table->boolean('is_related_it')->default(false);
            $table->boolean('is_related_energy')->default(false);
            $table->boolean('related_sales_support')->default(false);
            $table->boolean('related_it')->default(false);          // Information Technology
            $table->boolean('related_pe')->default(false);          // Process Engineering
            $table->boolean('related_ga')->default(false);          // General Affair
            $table->boolean('related_maintenance')->default(false); // Maintenance
            $table->boolean('related_hse')->default(false);         // Facility & HSE
            $table->boolean('related_qc')->default(false);          // Quality Control
            $table->boolean('related_energy')->default(false);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
