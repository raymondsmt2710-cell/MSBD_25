<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kandidat', function (Blueprint $table) {
            $table->increments('id_kandidat');
            $table->unsignedInteger('id_pemilihan');
            $table->unsignedTinyInteger('nomor_urut');
            $table->unsignedInteger('id_siswa_ketua');
            $table->unsignedInteger('id_siswa_wakil')->nullable();
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->string('foto_url', 255)->nullable();

            $table->unique(['id_pemilihan', 'nomor_urut']);
            $table->foreign('id_pemilihan')->references('id_pemilihan')->on('pemilihan')->cascadeOnDelete();
            $table->foreign('id_siswa_ketua')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_siswa_wakil')->references('id_siswa')->on('siswa')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kandidat');
    }
};
