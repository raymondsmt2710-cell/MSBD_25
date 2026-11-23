<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_presensi', function (Blueprint $table) {

            $table->unique(['id_jadwal', 'tanggal'], 'uq_sesi_jadwal_tanggal');
            $table->index(['tanggal', 'status'], 'idx_sesi_tanggal_status');
        });

        Schema::table('token_presensi', function (Blueprint $table) {
            $table->index(['id_sesi', 'kedaluwarsa_pada'], 'idx_token_sesi_kedaluwarsa');
        });

        Schema::table('presensi', function (Blueprint $table) {

            $table->index(['id_siswa', 'status'], 'idx_presensi_siswa_status');
        });

        Schema::table('pengajuan_izin', function (Blueprint $table) {

            $table->index(['id_siswa', 'status', 'tanggal_mulai', 'tanggal_selesai'], 'idx_izin_siswa_status_tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_izin', fn (Blueprint $t) => $t->dropIndex('idx_izin_siswa_status_tanggal'));
        Schema::table('presensi', fn (Blueprint $t) => $t->dropIndex('idx_presensi_siswa_status'));
        Schema::table('token_presensi', fn (Blueprint $t) => $t->dropIndex('idx_token_sesi_kedaluwarsa'));
        Schema::table('sesi_presensi', function (Blueprint $t) {
            $t->dropIndex('idx_sesi_tanggal_status');
            $t->dropUnique('uq_sesi_jadwal_tanggal');
        });
    }
};
