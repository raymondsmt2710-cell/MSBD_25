<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->dateTime('updated_at')->nullable()->after('created_at');
            $table->string('alasan_batal', 255)->nullable()->after('is_batal');
            $table->unsignedInteger('dibatalkan_oleh')->nullable()->after('alasan_batal');

            $table->foreign('dibatalkan_oleh')->references('id_user')->on('users')->nullOnDelete();
            $table->index(['id_kelas', 'tanggal']);
            $table->index(['id_kelas', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->dropForeign(['dibatalkan_oleh']);
            $table->dropIndex(['id_kelas', 'tanggal']);
            $table->dropIndex(['id_kelas', 'jenis']);
            $table->dropColumn(['updated_at', 'alasan_batal', 'dibatalkan_oleh']);
        });
    }
};
