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
        Schema::create('master_items', function (Blueprint $table) {
            $table->id();
            // Relasi ke Request (Agar tahu barang ini hasil usulan siapa)
            $table->foreignId('item_request_id')->nullable()->constrained('item_requests')->nullOnDelete();

            // Kolom Identitas (Gabungan Lama + Excel)
            $table->string('item_code')->unique()->nullable(); // Akan diisi oleh SC
            $table->string('serial_number')->unique()->nullable();
            $table->string('name'); // Dari form awal
            $table->string('unit'); // Satuan dasar (PCS/KG)
            $table->string('unit_child')->nullable(); // Satuan child (PCS/KG)
            $table->foreignId('department_id')->nullable()->constrained('departments');

            // Tambahan Kolom ERP Dasar
            $table->foreignId('item_group_id')->nullable()->constrained('item_groups')->nullOnDelete();
            $table->string('item_type')->nullable();
            $table->string('status')->default('active');

            // Fitur Lama Anda
            $table->boolean('requires_energy')->default(false);
            $table->json('ai_tags')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_items');
    }
};
