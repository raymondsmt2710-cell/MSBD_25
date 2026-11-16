<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suara anonim: tanpa id_siswa, hanya UUID + pemilihan + kandidat
        Schema::create('suara', function (Blueprint $table) {
            $table->char('id_suara', 36)->primary();
            $table->unsignedInteger('id_pemilihan');
            $table->unsignedInteger('id_kandidat');

            $table->foreign('id_pemilihan')->references('id_pemilihan')->on('pemilihan')->cascadeOnDelete();
            $table->foreign('id_kandidat')->references('id_kandidat')->on('kandidat')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suara');
    }
};
