<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->increments('id_kelas');
            $table->unsignedInteger('id_tahun_ajaran');
            $table->string('nama_kelas', 20);
            $table->unsignedTinyInteger('tingkat'); // 10, 11, 12
            $table->string('jurusan', 30)->nullable();
            $table->unsignedInteger('id_wali_kelas')->nullable();
            $table->string('ruangan', 20)->nullable();

            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran')->restrictOnDelete();
            $table->foreign('id_wali_kelas')->references('id_guru')->on('guru')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
