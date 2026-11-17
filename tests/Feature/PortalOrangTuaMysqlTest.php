<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Tes yang MEMERLUKAN MySQL sungguhan: view v_portal_anak_orang_tua dan
// stored function fn_persentase_kehadiran / fn_jumlah_presensi tidak ada di sqlite.
//
// Kelas ini sengaja TIDAK memakai RefreshDatabase/DatabaseTruncation: ia hanya
// membaca data yang sudah ada di database aplikasi, tidak menulis apa pun.
// Semua pemeriksaan di sini adalah "halaman benar-benar bisa dirender", sehingga
// kegagalan seperti 1267 (collation) atau grant yang kurang ikut terdeteksi —
// hal yang tidak mungkin ditangkap tes sqlite.
class PortalOrangTuaMysqlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Tes ini hanya berjalan pada MySQL.');
        }

        $this->withoutMiddleware(DBDynamicConnection::class);
    }

    /** Akun orang tua yang benar-benar ada di database (hasil seeder). */
    private function orangTuaSeeder(): User
    {
        $user = User::where('role', 'orang_tua')->orderBy('id_user')->first();

        if ($user === null) {
            $this->markTestSkipped('Belum ada akun orang_tua di database (jalankan seeder lebih dulu).');
        }

        return $user;
    }

    public function test_halaman_portal_benar_benar_dirender(): void
    {
        $this->actingAs($this->orangTuaSeeder())
            ->get('/ortu')
            ->assertOk()
            ->assertSee('Portal Pemantauan Orang Tua');
    }

    public function test_halaman_detail_anak_benar_benar_dirender(): void
    {
        $user = $this->orangTuaSeeder();

        $anak = DB::table('v_portal_anak_orang_tua')
            ->where('id_orang_tua', $user->orangTua->id_orang_tua)
            ->first();

        if ($anak === null) {
            $this->markTestSkipped('Akun orang_tua ini belum punya anak terhubung.');
        }

        $this->actingAs($user)
            ->get('/ortu/anak/'.$anak->id_siswa)
            ->assertOk()
            ->assertSee($anak->nama_siswa)
            ->assertSee('Ringkasan Kehadiran');
    }

    // Stored function dihitung MySQL. Kalau collation parameter salah, panggilan ini
    // melempar 1267 dan halaman menjadi 500 — persis bug yang pernah terjadi.
    public function test_stored_function_kehadiran_bisa_dipanggil(): void
    {
        $anak = DB::table('v_portal_anak_orang_tua')->first();

        if ($anak === null) {
            $this->markTestSkipped('Belum ada data anak.');
        }

        $persen = DB::selectOne('SELECT fn_persentase_kehadiran(?) AS p', [$anak->id_siswa]);
        $this->assertNotNull($persen);

        foreach (['hadir', 'izin', 'sakit', 'alpa'] as $status) {
            $r = DB::selectOne('SELECT fn_jumlah_presensi(?, ?) AS n', [$anak->id_siswa, $status]);
            $this->assertNotNull($r, "fn_jumlah_presensi gagal untuk status {$status}");
            $this->assertGreaterThanOrEqual(0, (int) $r->n);
        }
    }
}
