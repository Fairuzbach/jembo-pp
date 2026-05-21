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
        Schema::create('item_serializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained('master_items')->cascadeOnDelete();

            // Kolom Servis & Serial (Berdasarkan File 10, 11, 15)
            $table->boolean('is_serialized')->default(false)->comment('Apakah barang ini wajib catat Serial Number?');
            $table->integer('warranty_period_months')->default(0)->comment('Masa Garansi (Bulan)');
            $table->string('lifecycle_status')->nullable()->comment('Status Siklus Hidup Barang');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_serializations');
    }
};
