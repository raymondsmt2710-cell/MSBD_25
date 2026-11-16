<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panitia_pemilihan', function (Blueprint $table) {
            $table->unsignedInteger('id_pemilihan');
            $table->unsignedInteger('id_user');

            $table->primary(['id_pemilihan', 'id_user']);
            $table->foreign('id_pemilihan')->references('id_pemilihan')->on('pemilihan')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panitia_pemilihan');
    }
};
