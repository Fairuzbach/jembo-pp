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
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_item_id')->constrained();
            $table->integer('quantity');
            $table->string('usage_purpose');
            $table->date('requirement_date')->nullable();
            $table->string('dimension_code')->nullable();

            // Scoped Approval Status
            $table->string('internal_status')->default('pending');
            $table->text('rejection_reason')->nullable();

            // Tracking Infor ERP (Untuk fitur Batch PR)
            $table->string('infor_pr_number')->nullable();
            $table->timestamp('sent_to_infor_at')->nullable();
            $table->timestamp('direksi_approved_at')->nullable();
            $table->timestamp('sc_rejected_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
