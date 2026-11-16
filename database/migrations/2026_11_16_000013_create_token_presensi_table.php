<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_presensi', function (Blueprint $table) {
            $table->increments('id_token');
            $table->unsignedInteger('id_sesi');
            $table->string('kode_token', 64)->unique();
            $table->dateTime('dibuat_pada');
            $table->dateTime('kedaluwarsa_pada');
            $table->unsignedInteger('digunakan_oleh')->nullable();
            $table->dateTime('digunakan_pada')->nullable();

            $table->foreign('id_sesi')->references('id_sesi')->on('sesi_presensi')->cascadeOnDelete();
            $table->foreign('digunakan_oleh')->references('id_siswa')->on('siswa')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_presensi');
    }
};
