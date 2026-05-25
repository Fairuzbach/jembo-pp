<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');

            $table->foreignId('item_group_id')->nullable()->constrained('item_groups');
            $table->string('new_group_code')->nullable();
            $table->string('new_group_desc')->nullable();
            $table->boolean('is_group_synced')->default(false);
            $table->string('department_code')->nullable();
            $table->string('category_code')->nullable();
            $table->string('subcategory_code')->nullable();
            $table->string('type_code')->nullable();
            $table->string('item_code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('purpose')->nullable();
            $table->string('unit');
            $table->decimal('estimated_price', 15, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_requests');
    }
};
