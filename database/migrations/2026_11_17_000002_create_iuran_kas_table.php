<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuran_kas', function (Blueprint $table) {
            $table->increments('id_iuran');
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_siswa');
            $table->char('periode', 7);
            $table->decimal('nominal', 12, 2);
            $table->enum('status', ['belum_bayar', 'menunggu', 'terverifikasi', 'ditolak'])
                ->default('belum_bayar');
            $table->string('bukti_url', 255)->nullable();
            $table->unsignedInteger('id_transaksi')->nullable()->unique();
            $table->unsignedInteger('diverifikasi_oleh')->nullable();
            $table->dateTime('diverifikasi_pada')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['id_kelas', 'id_siswa', 'periode']);
            $table->index(['id_kelas', 'periode']);

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
            $table->foreign('id_transaksi')->references('id_transaksi')->on('transaksi_kas')->nullOnDelete();
            $table->foreign('diverifikasi_oleh')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iuran_kas');
    }
};
