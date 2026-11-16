<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daftar_pemilih', function (Blueprint $table) {
            $table->unsignedInteger('id_pemilihan');
            $table->unsignedInteger('id_siswa');
            $table->boolean('sudah_memilih')->default(false);
            $table->dateTime('waktu_memilih')->nullable();

            $table->primary(['id_pemilihan', 'id_siswa']);
            $table->foreign('id_pemilihan')->references('id_pemilihan')->on('pemilihan')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daftar_pemilih');
    }
};
