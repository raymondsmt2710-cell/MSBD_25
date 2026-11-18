<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi hak EXECUTE pada stored routine (error MySQL 1370).
 *
 * Konteks bug: `DROP FUNCTION` / `DROP PROCEDURE` di MySQL ikut menghapus GRANT
 * EXECUTE yang menempel. Migrasi 2026_11_16_000035 melakukan DROP pada
 * fn_jumlah_presensi lalu hanya memberi ulang hak ke siswa_app, sehingga
 * guru_app dan ortu_app kena "execute command denied ... for routine".
 *
 * Tes ini memastikan matriks hak akses sesuai rancangan 000033 tetap utuh,
 * sehingga kerusakan serupa langsung ketahuan — bukan menunggu sampai ada
 * pengguna yang membuka halaman dan mendapat 500.
 *
 * Dilewati di sqlite karena konsep GRANT/routine tidak ada di sana.
 */
class HakEksekusiRoutineTest extends TestCase
{
    /** @var array<int, string> */
    private array $db;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('GRANT dan stored routine hanya ada di MySQL/MariaDB.');
        }

        $this->db = [
            'guru' => env('DB_USERNAME_GURU', 'guru_app'),
            'siswa' => env('DB_USERNAME_SISWA', 'siswa_app'),
            'ortu' => env('DB_USERNAME_ORTU', 'ortu_app'),
        ];
    }

    /** Ambil daftar "EXECUTE ON FUNCTION `db`.`nama`" yang dimiliki seorang user. */
    private function hakEksekusi(string $user): string
    {
        $baris = DB::select("SHOW GRANTS FOR '{$user}'@'localhost'");

        return implode("\n", array_map(
            fn ($r) => (string) reset($r),
            $baris
        ));
    }

    /**
     * Cocokkan baris GRANT. MySQL menulis view sebagai
     * "GRANT SELECT ON `db`.`view`" (tanpa kata TABLE), sedangkan routine
     * memakai "GRANT EXECUTE ON FUNCTION `db`.`fn`". Karena itu kata kunci
     * "TABLE" dibuat opsional.
     */
    private function punyaHak(string $user, string $jenis, string $nama, string $hak = 'EXECUTE'): bool
    {
        $db = DB::connection()->getDatabaseName();
        $pola = "/{$hak} ON (?:{$jenis} )?`{$db}`\.`{$nama}`/i";

        return (bool) preg_match($pola, $this->hakEksekusi($user));
    }

    public function test_orang_tua_punya_hak_eksekusi_fn_jumlah_presensi(): void
    {
        $this->assertTrue(
            $this->punyaHak($this->db['ortu'], 'FUNCTION', 'fn_jumlah_presensi'),
            "ortu_app kehilangan EXECUTE pada fn_jumlah_presensi — portal /ortu akan error 1370."
        );
    }

    public function test_orang_tua_punya_hak_eksekusi_fn_persentase_kehadiran(): void
    {
        $this->assertTrue(
            $this->punyaHak($this->db['ortu'], 'FUNCTION', 'fn_persentase_kehadiran'),
            'ortu_app kehilangan EXECUTE pada fn_persentase_kehadiran.'
        );
    }

    public function test_guru_punya_hak_eksekusi_fn_jumlah_presensi(): void
    {
        $this->assertTrue(
            $this->punyaHak($this->db['guru'], 'FUNCTION', 'fn_jumlah_presensi'),
            'guru_app kehilangan EXECUTE pada fn_jumlah_presensi — roster guru akan error 1370.'
        );
    }

    public function test_siswa_punya_hak_eksekusi_fn_jumlah_presensi(): void
    {
        $this->assertTrue(
            $this->punyaHak($this->db['siswa'], 'FUNCTION', 'fn_jumlah_presensi'),
            'siswa_app kehilangan EXECUTE pada fn_jumlah_presensi.'
        );
    }

    public function test_orang_tua_tidak_punya_hak_tulis(): void
    {
        // Prinsip least privilege: portal wali hanya-baca. Kalau GRANT ini pernah
        // terbuka, fitur 7 berubah dari pemantauan menjadi alat manipulasi data.
        $hak = $this->hakEksekusi($this->db['ortu']);
        $db = DB::connection()->getDatabaseName();

        // Tabel infrastruktur (cache, cache_locks, sessions) sengaja butuh tulis
        // agar login berbasis session berjalan. Yang dilarang adalah tulis ke
        // tabel data sekolah.
        $hakData = implode("\n", array_filter(
            explode("\n", $hak),
            fn (string $baris) => ! preg_match('/`(?:cache|cache_locks|sessions)`/i', $baris)
        ));

        foreach (['INSERT', 'UPDATE', 'DELETE'] as $tulis) {
            $this->assertDoesNotMatchRegularExpression(
                "/GRANT .*{$tulis}.*ON `{$db}`\./i",
                $hakData,
                "ortu_app seharusnya tidak punya hak {$tulis}."
            );
        }
    }

    public function test_view_roster_dan_sesi_tersedia_untuk_siswa(): void
    {
        // View ini dibuat oleh migrasi 000035. Kalau migrasi itu terlewat,
        // /siswa/presensi/riwayat gagal dengan "table doesn't exist".
        $this->assertTrue(
            $this->punyaHak($this->db['siswa'], 'TABLE', 'v_roster_presensi_siswa', 'SELECT'),
            'siswa_app tidak punya SELECT pada v_roster_presensi_siswa.'
        );
        $this->assertTrue(
            $this->punyaHak($this->db['siswa'], 'TABLE', 'v_sesi_presensi_siswa', 'SELECT'),
            'siswa_app tidak punya SELECT pada v_sesi_presensi_siswa.'
        );
    }

    public function test_ketiga_peran_punya_hak_eksekusi_sp_ubah_sandi(): void
    {
        // Halaman /akun dipakai siswa, guru, dan orang tua. Tanpa EXECUTE pada
        // procedure ini, ganti sandi gagal 1370 di semua peran tersebut.
        foreach (['siswa', 'guru', 'ortu'] as $peran) {
            $this->assertTrue(
                $this->punyaHak($this->db[$peran], 'PROCEDURE', 'sp_ubah_sandi'),
                "{$this->db[$peran]} kehilangan EXECUTE pada sp_ubah_sandi — ganti sandi akan error 1370."
            );
        }
    }

    public function test_siswa_tetap_tidak_punya_hak_update_pada_users(): void
    {
        // Ganti sandi sengaja lewat procedure, BUKAN dengan memberi UPDATE pada
        // tabel users. Kalau hak ini pernah terbuka, siswa bisa mengubah
        // password_hash milik akun lain langsung lewat SQL.
        $hak = $this->hakEksekusi($this->db['siswa']);
        $db = DB::connection()->getDatabaseName();

        $this->assertDoesNotMatchRegularExpression(
            "/GRANT .*UPDATE.*ON `{$db}`\.`users`/i",
            $hak,
            'siswa_app seharusnya tidak punya UPDATE pada tabel users.'
        );
    }
}
