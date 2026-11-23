<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transaksi_kas', 'dibatalkan_oleh')) {
            Schema::table('transaksi_kas', function (Blueprint $table) {
                $table->unsignedInteger('dibatalkan_oleh')->nullable()->after('is_batal');
                $table->foreign('dibatalkan_oleh')->references('id_user')->on('users')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('transaksi_kas', 'dibatalkan_pada')) {
            Schema::table('transaksi_kas', function (Blueprint $table) {
                $table->dateTime('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
            });
        }

        if (! Schema::hasColumn('transaksi_kas', 'alasan_batal')) {
            Schema::table('transaksi_kas', function (Blueprint $table) {
                $table->string('alasan_batal', 255)->nullable()->after('dibatalkan_pada');
            });
        }
    }

    public function down(): void
    {


        if (Schema::hasColumn('transaksi_kas', 'dibatalkan_pada')) {
            Schema::table('transaksi_kas', function (Blueprint $table) {
                $table->dropColumn('dibatalkan_pada');
            });
        }
    }
};
