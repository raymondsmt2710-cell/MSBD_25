<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_pemilihan', function (Blueprint $table) {
            $table->increments('id_log');
            $table->unsignedInteger('id_pemilihan');
            $table->unsignedInteger('id_user')->nullable();
            $table->string('aksi', 40);
            $table->string('status_lama', 20)->nullable();
            $table->string('status_baru', 20)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->dateTime('dibuat_pada')->useCurrent();

            $table->index('id_pemilihan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_pemilihan');
    }
};
