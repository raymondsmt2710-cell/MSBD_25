<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posisi_duduk', function (Blueprint $table) {
            $table->enum('sisi', ['kiri', 'kanan'])->default('kiri')->after('kolom');
        });

        Schema::table('posisi_duduk', function (Blueprint $table) {
            $table->dropUnique(['id_denah', 'baris', 'kolom']);
            $table->unique(['id_denah', 'baris', 'kolom', 'sisi'], 'uq_posisi_meja_sisi');
        });
    }

    public function down(): void
    {
        Schema::table('posisi_duduk', function (Blueprint $table) {
            $table->dropUnique('uq_posisi_meja_sisi');
            $table->unique(['id_denah', 'baris', 'kolom']);
            $table->dropColumn('sisi');
        });
    }
};
