<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->increments('id_presensi');
            $table->unsignedInteger('id_sesi');
            $table->unsignedInteger('id_siswa');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa']);
            $table->enum('metode', ['barcode', 'manual'])->default('barcode');
            $table->dateTime('waktu_presensi')->nullable();
            $table->unsignedInteger('id_token')->nullable()->unique();
            $table->unsignedInteger('id_pengajuan')->nullable();
            $table->unsignedInteger('diubah_oleh')->nullable();
            $table->string('catatan', 255)->nullable();

            $table->unique(['id_sesi', 'id_siswa']);
            $table->foreign('id_sesi')->references('id_sesi')->on('sesi_presensi')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
            $table->foreign('id_token')->references('id_token')->on('token_presensi')->nullOnDelete();
            $table->foreign('id_pengajuan')->references('id_pengajuan')->on('pengajuan_izin')->nullOnDelete();
            $table->foreign('diubah_oleh')->references('id_guru')->on('guru')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
