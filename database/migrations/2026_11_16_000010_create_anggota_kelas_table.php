<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_kelas', function (Blueprint $table) {
            $table->increments('id_anggota');
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_siswa');
            $table->unsignedTinyInteger('no_absen')->nullable();

            $table->unique(['id_kelas', 'id_siswa']);
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_kelas');
    }
};
