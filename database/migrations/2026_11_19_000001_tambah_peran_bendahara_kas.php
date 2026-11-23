<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Peran baru: siswa dengan hak kelola kas (bendahara_kas)
|--------------------------------------------------------------------------
| Akun ini tetap punya profil di tabel `siswa`, sehingga fitur presensi,
| perangkat, dan pemilihan tetap berlaku. Bedanya hanya pada akses kas:
| Akses::bisaKelolaKas() mengizinkannya mencatat/membatalkan transaksi dan
| memverifikasi iuran pada kelasnya sendiri — sama seperti bendahara.
|
| Kolom `role` adalah ENUM. Di MySQL itu tipe kolom sungguhan; di SQLite
| Laravel menerjemahkannya menjadi CHECK constraint. Keduanya harus diubah
| eksplisit, karena tanpa itu INSERT dengan nilai baru akan ditolak.
*/
return new class extends Migration
{
    private const NILAI_LAMA = ['admin', 'guru', 'siswa', 'orang_tua'];

    private const NILAI_BARU = ['admin', 'guru', 'siswa', 'orang_tua', 'bendahara_kas'];

    public function up(): void
    {
        $this->ubahEnum(self::NILAI_BARU);
    }

    public function down(): void
    {
        // Kembalikan akun berperan baru ke 'siswa' sebelum nilai enum dicabut,
        // agar tidak ada baris yang menyimpan nilai yang tidak lagi dikenal.
        DB::table('users')->where('role', 'bendahara_kas')->update(['role' => 'siswa']);

        $this->ubahEnum(self::NILAI_LAMA);
    }

    /** @param array<int, string> $nilai */
    private function ubahEnum(array $nilai): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $daftar = collect($nilai)->map(fn ($item) => "'{$item}'")->implode(', ');
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM({$daftar}) NOT NULL");

            return;
        }

        if ($driver === 'sqlite') {
            $this->ubahEnumSqlite($nilai);
        }

        // PostgreSQL memakai tipe tersendiri; proyek ini hanya memakai MySQL/SQLite.
    }

    /**
     * SQLite tidak bisa mengubah CHECK constraint lewat ALTER TABLE, jadi tabel
     * dibangun ulang: tabel baru, salin data, buang tabel lama, ganti nama.
     *
     * @param  array<int, string>  $nilai
     */
    private function ubahEnumSqlite(array $nilai): void
    {
        $sementara = 'users_baru_role';

        Schema::dropIfExists($sementara);

        Schema::create($sementara, function (Blueprint $table) use ($nilai) {
            $table->increments('id_user');
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);
            $table->enum('role', $nilai);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login')->nullable();
            $table->timestamps();
        });

        $kolom = ['id_user', 'username', 'password_hash', 'role', 'is_active', 'last_login', 'created_at', 'updated_at'];

        DB::table($sementara)->insertUsing(
            $kolom,
            DB::table('users')->select($kolom),
        );

        Schema::drop('users');
        Schema::rename($sementara, 'users');
    }
};
