<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruangan_sekolah', function (Blueprint $table) {
            $table->increments('id_ruangan');
            $table->string('kode_ruangan', 20)->unique();
            $table->string('nama_ruangan', 100);
            $table->enum('kategori', [
                'kelas', 'laboratorium', 'perpustakaan', 'ruang_guru',
                'kantin', 'uks', 'toilet', 'lainnya',
            ]);
            $table->string('gedung', 50)->default('Gedung Utama');
            $table->unsignedTinyInteger('lantai')->default(1);
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->json('fasilitas')->nullable();
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('posisi_x')->default(0);
            $table->unsignedSmallInteger('posisi_y')->default(0);
            $table->unsignedTinyInteger('lebar_grid')->default(1);
            $table->unsignedTinyInteger('tinggi_grid')->default(1);
            $table->enum('status', ['tersedia', 'maintenance', 'tutup'])->default('tersedia');
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->string('gambar_url', 255)->nullable();
            $table->timestamps();

            $table->index(['gedung', 'lantai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruangan_sekolah');
    }
};
