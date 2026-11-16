<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_kas', function (Blueprint $table) {
            $table->increments('id_transaksi');
            $table->unsignedInteger('id_kelas');
            $table->enum('jenis', ['pemasukan', 'pengeluaran']);
            $table->string('kategori', 50);
            $table->decimal('nominal', 12, 2);
            $table->date('tanggal');
            $table->string('keterangan', 255)->nullable();
            $table->unsignedInteger('id_siswa')->nullable();
            $table->string('bukti_url', 255)->nullable();
            $table->unsignedInteger('dicatat_oleh');
            $table->boolean('is_batal')->default(false);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->nullOnDelete();
            $table->foreign('dicatat_oleh')->references('id_user')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_kas');
    }
};
