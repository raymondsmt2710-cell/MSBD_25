<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->increments('id_jadwal');
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_mapel');
            $table->unsignedInteger('id_guru');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            $table->foreign('id_mapel')->references('id_mapel')->on('mata_pelajaran')->restrictOnDelete();
            $table->foreign('id_guru')->references('id_guru')->on('guru')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pelajaran');
    }
};
