<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fitur admin no. 19: admin boleh membatalkan transaksi kas (is_batal) sebagai koreksi/audit.
// Kolom is_batal saja tidak menjawab "siapa, kapan, dan kenapa", padahal itulah inti audit.
// Ketiga kolom ini nullable sehingga data lama dan alur bendahara tidak berubah.
//
// Catatan merge: migrasi branch kas (2026_11_17_000001) sudah menambahkan
// dibatalkan_oleh dan alasan_batal. Karena itu penambahan di sini dibuat
// idempoten agar database baru maupun lama sama-sama aman.
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
        // dibatalkan_oleh dan alasan_batal dikelola juga oleh migrasi kas
        // 2026_11_17_000001; di sini cukup lepas kolom yang memang milik migrasi ini.
        if (Schema::hasColumn('transaksi_kas', 'dibatalkan_pada')) {
            Schema::table('transaksi_kas', function (Blueprint $table) {
                $table->dropColumn('dibatalkan_pada');
            });
        }
    }
};
