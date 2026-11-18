<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * sp_ubah_sandi diuji langsung di MySQL, bukan hanya keberadaan GRANT-nya.
 *
 * Kenapa tes terpisah: tes perilaku (AkunUbahSandiTest) berjalan di SQLite, di
 * mana procedure tidak ada sehingga controller memakai jalur Eloquent. Artinya
 * seluruh isi procedure — perbandingan BINARY, penjagaan ROW_COUNT(), dan
 * pengisian @status — tidak tersentuh di sana. Persis jenis kesalahan yang
 * pernah lolos di proyek ini: GRANT ada, tapi routine-nya sendiri salah
 * (lihat HakEksekusiRoutineTest dan migrasi 2026_11_17_000015).
 *
 * Panggilan di bawah memakai koneksi mysql_siswa, yaitu user MySQL yang sama
 * dengan yang dipakai DBDynamicConnection di produksi. Jadi yang diuji benar-
 * benar jalur "siswa_app memanggil procedure", termasuk hak EXECUTE-nya.
 *
 * Dilewati bila MySQL tidak tersedia (mis. CI yang hanya punya SQLite).
 */
class UbahSandiProcedureMysqlTest extends TestCase
{
    // DatabaseTruncation, bukan RefreshDatabase: RefreshDatabase membungkus tes
    // dalam transaksi pada koneksi `mysql`, sedangkan procedure dipanggil lewat
    // koneksi terpisah `mysql_siswa`. Transaksi yang belum commit tidak terlihat
    // dari koneksi lain, sehingga akun uji tampak tidak ada. Ini pola yang sama
    // dengan tests/Feature/Voting/GrantTest.php.
    use DatabaseTruncation;

    /** Tabel yang harus dikosongkan antar tes. */
    protected array $tablesToTruncate = ['users'];

    private string $sandiLama = 'sandi-lama-123';

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL tidak tersedia: '.$e->getMessage());
        }

        $this->withoutMiddleware(DBDynamicConnection::class);
    }

    /** Membuat akun siswa dan mengembalikan [id_user, hash]. */
    private function buatAkun(): array
    {
        $hash = Hash::make($this->sandiLama);

        $idUser = DB::table('users')->insertGetId([
            'username' => 'sp_sandi_uji',
            'password_hash' => $hash,
            'role' => 'siswa',
            'is_active' => true,
        ]);

        return [$idUser, $hash];
    }

    /** Memanggil procedure lewat koneksi siswa_app dan mengembalikan @status. */
    private function panggil(int $idUser, string $hashLama, string $sandiBaru): ?string
    {
        $koneksi = DB::connection('mysql_siswa');
        $koneksi->statement('SET @status_sandi = NULL');
        $koneksi->statement('CALL sp_ubah_sandi(?, ?, ?, @status_sandi)', [
            $idUser,
            $hashLama,
            Hash::make($sandiBaru),
        ]);

        return $koneksi->selectOne('SELECT @status_sandi AS s')->s;
    }

    public function test_siswa_app_bisa_mengganti_sandi_lewat_procedure(): void
    {
        [$idUser, $hash] = $this->buatAkun();

        $status = $this->panggil($idUser, $hash, 'sandi-baru-456');

        $this->assertSame('BERHASIL', $status);
        $this->assertTrue(Hash::check('sandi-baru-456', User::find($idUser)->password_hash));
    }

    public function test_hash_lama_yang_sudah_tidak_cocok_ditolak_dan_tidak_menulis(): void
    {
        // Meniru balapan: aplikasi sudah memverifikasi sandi lama, tetapi admin
        // mereset sandi sebelum procedure sempat menulis. Hash di DB harus tetap.
        [$idUser, $hash] = $this->buatAkun();

        DB::table('users')->where('id_user', $idUser)->update([
            'password_hash' => Hash::make('direset-admin'),
        ]);
        $hashSetelahReset = DB::table('users')->where('id_user', $idUser)->value('password_hash');

        $status = $this->panggil($idUser, $hash, 'sandi-baru-456');

        $this->assertSame('SANDI_LAMA_SALAH', $status);
        $this->assertSame(
            $hashSetelahReset,
            DB::table('users')->where('id_user', $idUser)->value('password_hash'),
            'Procedure menulis walau hash lama sudah tidak cocok.'
        );
    }

    public function test_perbandingan_hash_peka_huruf_besar_kecil(): void
    {
        // Kolom password_hash memakai collation utf8mb4_unicode_ci. Tanpa BINARY,
        // hash yang hanya beda kapitalisasi dianggap sama dan penulisan lolos.
        [$idUser, $hash] = $this->buatAkun();

        $hashBedaKapital = strtoupper($hash);
        $this->assertNotSame($hash, $hashBedaKapital, 'Hash uji tidak berubah saat di-uppercase.');

        $status = $this->panggil($idUser, $hashBedaKapital, 'sandi-baru-456');

        $this->assertSame('SANDI_LAMA_SALAH', $status);
        $this->assertSame($hash, DB::table('users')->where('id_user', $idUser)->value('password_hash'));
    }

    public function test_id_user_tidak_dikenal_dilaporkan(): void
    {
        $status = $this->panggil(999999, 'hash-apa-saja', 'sandi-baru-456');

        $this->assertSame('AKUN_TIDAK_DITEMUKAN', $status);
    }

    public function test_siswa_app_tidak_bisa_update_users_langsung(): void
    {
        // Bukti bahwa procedure memang diperlukan: jalur langsung harus ditolak DB.
        [$idUser] = $this->buatAkun();

        $this->expectException(\Throwable::class);

        DB::connection('mysql_siswa')->update(
            'UPDATE users SET password_hash = ? WHERE id_user = ?',
            [Hash::make('lewat-jalur-langsung'), $idUser]
        );
    }
}
