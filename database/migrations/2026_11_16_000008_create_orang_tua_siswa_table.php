<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang_tua_siswa', function (Blueprint $table) {
            $table->unsignedInteger('id_orang_tua');
            $table->unsignedInteger('id_siswa');
            $table->enum('hubungan', ['ayah', 'ibu', 'wali']);
            $table->boolean('is_kontak_utama')->default(false);

            $table->primary(['id_orang_tua', 'id_siswa']);
            $table->foreign('id_orang_tua')->references('id_orang_tua')->on('orang_tua')->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orang_tua_siswa');
    }
};
