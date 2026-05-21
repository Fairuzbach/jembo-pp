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
        Schema::create('item_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained('master_items')->cascadeOnDelete();

            // Kolom Gudang (Berdasarkan File 3 - 7)
            $table->string('warehouse_code')->nullable()->comment('Kode Gudang Utama');
            $table->string('handling_unit')->nullable()->comment('Tipe Kemasan / Handling Unit');
            $table->decimal('weight', 10, 2)->nullable()->comment('Berat Barang');
            $table->decimal('length', 10, 2)->nullable()->comment('Panjang');
            $table->decimal('width', 10, 2)->nullable()->comment('Lebar');
            $table->decimal('height', 10, 2)->nullable()->comment('Tinggi');
            $table->string('storage_condition')->nullable()->comment('Suhu/Kondisi Penyimpanan');
            $table->boolean('hazardous_material')->default(false);
            $table->string('class_of_risk')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_warehouses');
    }
};
