<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengurus_kelas', function (Blueprint $table) {
            $table->increments('id_pengurus_kelas');
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_jabatan');

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
            $table->foreign('id_jabatan')->references('id_jabatan')->on('jabatan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengurus_kelas');
    }
};
