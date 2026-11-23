<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{


    public function up(): void
    {
        Schema::create('log_presensi', function (Blueprint $table) {
            $table->increments('id_log');
            $table->unsignedInteger('id_presensi');
            $table->unsignedInteger('id_sesi');
            $table->unsignedInteger('id_siswa');
            $table->string('aksi', 30);
            $table->string('status_lama', 10)->nullable();
            $table->string('status_baru', 10);
            $table->string('keterangan', 255)->nullable();
            $table->dateTime('dicatat_pada')->useCurrent();

            $table->index('id_presensi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_presensi');
    }
};
