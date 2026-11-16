<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisasi', function (Blueprint $table) {
            $table->increments('id_organisasi');
            $table->string('nama_organisasi', 100)->unique();
            $table->string('singkatan', 20)->nullable();
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('id_pembina')->nullable();
            $table->string('logo_url', 255)->nullable();

            $table->foreign('id_pembina')->references('id_guru')->on('guru')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisasi');
    }
};
