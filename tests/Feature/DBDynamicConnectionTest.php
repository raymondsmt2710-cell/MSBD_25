<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Middleware\DBDynamicConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Middleware DBDynamicConnection — menguji perilaku penggantian koneksi
 * tanpa menyentuh database (tidak perlu RefreshDatabase).
 *
 * Catatan: middleware ini otomatis non-aktif saat runningUnitTests() agar data
 * test tidak "hilang" saat koneksi berpindah ke user MySQL per-role. Karena itu
 * method privat `pakaiKoneksiRole()` dipanggil langsung lewat refleksi.
 */
class DBDynamicConnectionTest extends TestCase
{
    private function panggilPakaiKoneksiRole(string $asal): void
    {
        $mw = new DBDynamicConnection;
        $ref = new \ReflectionMethod($mw, 'pakaiKoneksiRole');
        $ref->setAccessible(true);
        $ref->invoke($mw, $asal);
    }

    private function pengguna(Role $role): object
    {
        // Objek ringan: cukup punya properti role untuk dibaca middleware.
        return new class($role)
        {
            public function __construct(public Role $role) {}
        };
    }

    private function loginSebagai(Role $role): void
    {
        $this->actingAs(new \App\Models\User([
            'username' => 'uji_'.$role->value,
            'role' => $role,
            'is_active' => true,
        ]));
    }

    public function test_koneksi_bawaan_tetap_dipakai_saat_koneksi_mysql_role_gagal(): void
    {
        config([
            'database.default' => 'mysql_sementara',
            'database.connections.mysql_sementara' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 1,
                'database' => 'tidak_ada',
                'username' => 'x',
                'password' => 'x',
            ],
            'database.connections.mysql_guru.port' => 1,
        ]);

        $this->loginSebagai(Role::Guru);

        $this->panggilPakaiKoneksiRole('mysql_sementara');

        // Gagal konek -> koneksi aplikasi tetap dipakai, tidak melempar exception.
        $this->assertSame('mysql_sementara', config('database.default'));
    }

    public function test_role_tanpa_koneksi_terdaftar_tidak_mengubah_koneksi(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.mysql_orangtua' => null]);

        $this->loginSebagai(Role::OrangTua);

        $this->panggilPakaiKoneksiRole('sqlite');

        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_middleware_tidak_mengganti_koneksi_saat_unit_test(): void
    {
        config(['database.default' => 'sqlite']);

        $this->loginSebagai(Role::Admin);

        (new DBDynamicConnection)->handle(Request::create('/'), fn () => response('ok'));

        // Saat test, koneksi tidak dipindah ke mysql_admin.
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }
}
