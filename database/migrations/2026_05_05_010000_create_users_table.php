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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique(); // Tambahkan baris ini
            $table->string('name');
            $table->string('email')->unique()->nullable(); // Buat nullable jika login menggunakan NIK
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Relasi Department yang kita buat sebelumnya
            $table->foreignId('department_id')->nullable()->constrained('departments');

            // Tambahkan kolom tambahan dari Excel jika diperlukan
            $table->string('job_position')->nullable();
            $table->string('job_level')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
