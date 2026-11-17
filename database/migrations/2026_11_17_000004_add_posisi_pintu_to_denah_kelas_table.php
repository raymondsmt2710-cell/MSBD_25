<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('denah_kelas', function (Blueprint $table) {
            $table->enum('posisi_pintu', ['kiri', 'kanan', 'belakang'])
                ->default('kanan')
                ->after('posisi_meja_guru');
        });
    }

    public function down(): void
    {
        Schema::table('denah_kelas', function (Blueprint $table) {
            $table->dropColumn('posisi_pintu');
        });
    }
};
