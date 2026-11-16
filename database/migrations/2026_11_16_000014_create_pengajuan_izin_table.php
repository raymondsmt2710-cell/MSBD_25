<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_izin', function (Blueprint $table) {
            $table->increments('id_pengajuan');
            $table->unsignedInteger('id_siswa');
            $table->enum('jenis', ['izin', 'sakit']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('alasan')->nullable();
            $table->string('lampiran_url', 255)->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->unsignedInteger('diverifikasi_oleh')->nullable();
            $table->dateTime('diajukan_pada');
            $table->dateTime('diverifikasi_pada')->nullable();

            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
            $table->foreign('diverifikasi_oleh')->references('id_guru')->on('guru')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_izin');
    }
};
