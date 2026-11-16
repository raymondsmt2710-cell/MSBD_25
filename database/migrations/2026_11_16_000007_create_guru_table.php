<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru', function (Blueprint $table) {
            $table->increments('id_guru');
            $table->unsignedInteger('id_user')->unique();
            $table->string('nip', 20)->unique();
            $table->string('nama_guru', 100);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('no_hp', 15)->nullable();
            $table->string('email', 100)->nullable();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru');
    }
};
