<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('denah_kelas', function (Blueprint $table) {
            $table->increments('id_denah');
            $table->unsignedInteger('id_kelas');
            $table->unsignedTinyInteger('jumlah_baris');
            $table->unsignedTinyInteger('jumlah_kolom');
            $table->enum('posisi_meja_guru', ['depan_kiri', 'depan_tengah', 'depan_kanan']);
            $table->date('berlaku_mulai');
            $table->unsignedInteger('dibuat_oleh');
            $table->boolean('is_aktif')->default(true);

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            $table->foreign('dibuat_oleh')->references('id_user')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('denah_kelas');
    }
};
