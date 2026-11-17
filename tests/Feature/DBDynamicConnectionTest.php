<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DBDynamicConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_koneksi_bawaan_tetap_dipakai_saat_koneksi_mysql_role_gagal(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.mysql_guru.port' => 1,
        ]);

        $user = User::create([
            'username' => 'guru_uji',
            'password_hash' => bcrypt('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        (new DBDynamicConnection)->handle(Request::create('/'), fn () => response('ok'));

        $this->assertSame('sqlite', config('database.default'));
    }
}
