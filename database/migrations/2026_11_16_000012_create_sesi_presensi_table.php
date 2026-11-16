<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_presensi', function (Blueprint $table) {
            $table->increments('id_sesi');
            $table->unsignedInteger('id_jadwal');
            $table->date('tanggal');
            $table->unsignedInteger('dibuka_oleh')->nullable();
            $table->dateTime('dibuka_pada')->nullable();
            $table->dateTime('ditutup_pada')->nullable();
            $table->enum('status', ['dibuka', 'ditutup'])->default('dibuka');

            $table->foreign('id_jadwal')->references('id_jadwal')->on('jadwal_pelajaran')->cascadeOnDelete();
            $table->foreign('dibuka_oleh')->references('id_guru')->on('guru')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_presensi');
    }
};
