<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);
            $table->enum('role', ['admin', 'guru', 'siswa', 'orang_tua']);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
