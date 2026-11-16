<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengurus_organisasi', function (Blueprint $table) {
            $table->increments('id_pengurus_org');
            $table->unsignedInteger('id_organisasi');
            $table->unsignedInteger('id_tahun_ajaran');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_jabatan');
            $table->string('bidang', 50)->nullable();

            $table->foreign('id_organisasi')->references('id_organisasi')->on('organisasi')->cascadeOnDelete();
            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran')->restrictOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
            $table->foreign('id_jabatan')->references('id_jabatan')->on('jabatan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengurus_organisasi');
    }
};
