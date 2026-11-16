<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemilihan', function (Blueprint $table) {
            $table->increments('id_pemilihan');
            $table->unsignedInteger('id_organisasi');
            $table->unsignedInteger('id_tahun_ajaran');
            $table->string('judul', 100);
            $table->text('deskripsi')->nullable();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->enum('status', ['draf', 'berlangsung', 'selesai'])->default('draf');
            $table->unsignedInteger('dibuat_oleh');

            $table->foreign('id_organisasi')->references('id_organisasi')->on('organisasi')->cascadeOnDelete();
            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran')->restrictOnDelete();
            $table->foreign('dibuat_oleh')->references('id_user')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemilihan');
    }
};
