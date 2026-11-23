<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UbahSandiProcedureMysqlTest extends TestCase
{





    use DatabaseTruncation;


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

        [$idUser] = $this->buatAkun();

        $this->expectException(\Throwable::class);

        DB::connection('mysql_siswa')->update(
            'UPDATE users SET password_hash = ? WHERE id_user = ?',
            [Hash::make('lewat-jalur-langsung'), $idUser]
        );
    }
}
