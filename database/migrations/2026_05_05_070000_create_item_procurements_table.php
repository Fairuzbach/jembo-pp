<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('item_procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained('master_items')->cascadeOnDelete();

            // Kolom Procurement (Berdasarkan File 8, 9, 12)
            $table->string('buy_from_bp')->nullable()->comment('Kode Vendor / Buy-from Business Partner');
            $table->string('purchase_unit', 50)->nullable()->comment('Satuan Pembelian (Bisa beda dgn satuan dasar)');
            $table->decimal('standard_cost', 15, 2)->nullable()->comment('Biaya Standar / Standard Cost');
            $table->string('currency', 10)->default('IDR')->comment('Mata Uang');
            $table->string('tax_code')->nullable()->comment('Kode Pajak (PPN)');
            $table->decimal('safety_stock', 10, 2)->default(0);
            $table->decimal('reorder_point', 10, 2)->default(0);
            $table->decimal('min_order_qty', 10, 2)->default(0);
            $table->decimal('max_order_qty', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_procurements');
    }
};
