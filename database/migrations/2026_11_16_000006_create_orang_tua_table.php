<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang_tua', function (Blueprint $table) {
            $table->increments('id_orang_tua');
            $table->unsignedInteger('id_user')->unique();
            $table->string('nama', 100);
            $table->string('no_hp', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('pekerjaan', 50)->nullable();
            $table->text('alamat')->nullable();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orang_tua');
    }
};
