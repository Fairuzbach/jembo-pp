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
        Schema::create('item_order_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained('master_items')->cascadeOnDelete();

            // Kolom Pemesanan (Berdasarkan File 13 & 14)
            $table->string('order_method')->nullable()->comment('Metode Order (ex: Reorder Point, Lot-for-Lot)');
            $table->integer('safety_stock')->default(0)->comment('Batas Stok Aman');
            $table->integer('reorder_point')->default(0)->comment('Titik Pemesanan Ulang');
            $table->integer('min_order_qty')->default(0)->comment('Batas Minimal Order');
            $table->integer('max_order_qty')->default(0)->comment('Batas Maksimal Order');
            $table->string('planner_code')->nullable()->comment('Kode Perencana / Planner');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_order_rules');
    }
};
