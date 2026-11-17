<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fitur admin no. 19: admin boleh membatalkan transaksi kas (is_batal) sebagai koreksi/audit.
// Kolom is_batal saja tidak menjawab "siapa, kapan, dan kenapa", padahal itulah inti audit.
// Ketiga kolom ini nullable sehingga data lama dan alur bendahara tidak berubah.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->unsignedInteger('dibatalkan_oleh')->nullable()->after('is_batal');
            $table->dateTime('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
            $table->string('alasan_batal', 255)->nullable()->after('dibatalkan_pada');

            $table->foreign('dibatalkan_oleh')->references('id_user')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->dropForeign(['dibatalkan_oleh']);
            $table->dropColumn(['dibatalkan_oleh', 'dibatalkan_pada', 'alasan_batal']);
        });
    }
};
