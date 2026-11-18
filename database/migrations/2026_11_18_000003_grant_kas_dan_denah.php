<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// DCL: hak akses fitur Kas (4) dan Denah (6) untuk user MySQL per role.
//
// Latar belakang: DBDynamicConnection mengganti koneksi ke guru_app/siswa_app/ortu_app,
// tetapi belum ada migrasi yang memberi hak atas tabel kas dan denah. Akibatnya halaman
// /kas dan /denah selalu 500 ("1142 SELECT command denied") untuk semua role selain admin.
// Test tidak menangkapnya karena DBDynamicConnection dimatikan saat runningUnitTests().
//
// Prinsip pembagian hak:
// - Semua role yang boleh melihat kelas (guru, siswa, orang tua) butuh SELECT pada tabel
//   yang dibaca Akses::daftarKelas(), KasService, dan DenahService.
// - Hak tulis hanya untuk siswa_app, karena bendahara (kas) dan ketua kelas (kursi) adalah
//   akun siswa. Pembatasan "hanya bendahara/ketua" tetap dijaga Gate kas.kelola dan
//   denah.kelola-kursi di aplikasi; MySQL tidak bisa membedakan siswa per jabatan.
// - Tidak ada DELETE pada transaksi_kas/iuran_kas: transaksi hanya dibatalkan (is_batal).
// - Ruangan sekolah hanya dikelola admin (admin_app sudah ALL PRIVILEGES), role lain SELECT.
// - ortu_app tetap hanya-baca (HakEksekusiRoutineTest mewajibkannya).
//
// Idempoten: GRANT boleh diulang. Setiap GRANT dijalankan terpisah agar satu kegagalan
// tidak menghentikan yang lain.
return new class extends Migration
{
    /** Tabel yang dibaca saat menentukan kelas dan menampilkan kas/denah. */
    private const BACA = [
        'kelas', 'anggota_kelas', 'tahun_ajaran', 'siswa', 'guru', 'users',
        'jabatan', 'pengurus_kelas', 'jadwal_pelajaran', 'mata_pelajaran',
        'transaksi_kas', 'iuran_kas', 'denah_kelas', 'posisi_duduk', 'ruangan_sekolah',
    ];

    /** Hak tulis siswa_app (bendahara dan ketua kelas). */
    private const TULIS_SISWA = [
        'transaksi_kas' => 'INSERT, UPDATE',
        'iuran_kas' => 'INSERT, UPDATE',
        'denah_kelas' => 'INSERT, UPDATE',
        'posisi_duduk' => 'INSERT, UPDATE, DELETE',
    ];

    /** Tabel milik fitur kas/denah; hanya ini yang dicabut saat rollback. */
    private const MILIK_FITUR = [
        'transaksi_kas', 'iuran_kas', 'denah_kelas', 'posisi_duduk', 'ruangan_sekolah',
        'jabatan', 'pengurus_kelas',
    ];

    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    /** @return array<string, string> */
    private function akun(): array
    {
        return [
            'guru' => env('DB_USERNAME_GURU', 'guru_app'),
            'siswa' => env('DB_USERNAME_SISWA', 'siswa_app'),
            'ortu' => env('DB_USERNAME_ORTU', 'ortu_app'),
        ];
    }

    private function jalankan(string $sql, array $kodeAman): void
    {
        try {
            DB::unprepared($sql);
        } catch (QueryException $e) {
            $kode = (int) ($e->errorInfo[1] ?? 0);
            if (! in_array($kode, $kodeAman, true)) {
                throw $e;
            }
            Log::error("Migrasi 2026_11_18_000003 melewati: {$sql}. Kode {$kode}: ".$e->getMessage());
        }
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        if (filter_var(env('DB_SKIP_ROLE_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::warning('Migrasi 2026_11_18_000003 dilewati karena DB_SKIP_ROLE_USERS=true.');

            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');
        // 1044/1142/1227 = tidak punya hak GRANT; 1146 = tabel tidak ada; 1133 = user tidak ada.
        $aman = [1044, 1133, 1142, 1146, 1227];

        foreach ($this->akun() as $user) {
            foreach (self::BACA as $tabel) {
                $this->jalankan("GRANT SELECT ON `{$db}`.`{$tabel}` TO '{$user}'@'{$host}'", $aman);
            }
        }

        $siswa = $this->akun()['siswa'];
        foreach (self::TULIS_SISWA as $tabel => $hak) {
            $this->jalankan("GRANT {$hak} ON `{$db}`.`{$tabel}` TO '{$siswa}'@'{$host}'", $aman);
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        // Tabel bersama (kelas, siswa, users, dst.) tidak dicabut karena juga dipakai
        // fitur presensi dan pemilihan. 1141/1147 = hak memang tidak ada.
        foreach ($this->akun() as $user) {
            foreach (self::MILIK_FITUR as $tabel) {
                $this->jalankan("REVOKE ALL PRIVILEGES ON `{$db}`.`{$tabel}` FROM '{$user}'@'{$host}'", [1141, 1147, 1133, 1146]);
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }
};
