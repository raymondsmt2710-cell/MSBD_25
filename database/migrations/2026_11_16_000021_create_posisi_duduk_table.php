<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posisi_duduk', function (Blueprint $table) {
            $table->increments('id_posisi');
            $table->unsignedInteger('id_denah');
            $table->unsignedInteger('id_siswa');
            $table->unsignedTinyInteger('baris');
            $table->unsignedTinyInteger('kolom');

            $table->unique(['id_denah', 'id_siswa']);
            $table->unique(['id_denah', 'baris', 'kolom']);
            $table->foreign('id_denah')->references('id_denah')->on('denah_kelas')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posisi_duduk');
    }
};
