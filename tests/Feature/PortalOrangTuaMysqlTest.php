<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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
