<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoMenujuBerandaTest extends TestCase
{
    use DatabaseTruncation;

    private function buatAkun(string $role, string $username): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);

        if ($role === 'siswa') {
            DB::table('siswa')->insert([
                'id_user' => $idUser,
                'nis' => '2026'.$idUser,
                'nisn' => '002026'.$idUser,
                'nama_siswa' => 'Siswa Logo',
                'jenis_kelamin' => 'L',
                'status' => 'aktif',
            ]);
        }

        if ($role === 'guru') {
            DB::table('guru')->insert([
                'id_user' => $idUser,
                'nip' => '1987'.$idUser,
                'nama_guru' => 'Guru Logo',
                'jenis_kelamin' => 'L',
            ]);
        }

        if ($role === 'orang_tua') {
            DB::table('orang_tua')->insert([
                'id_user' => $idUser,
                'nama' => 'Ortu Logo',
            ]);
        }

        return User::find($idUser);
    }


    private function hrefLogo(string $html): ?string
    {
        if (preg_match('/<a href="([^"]*)" class="brand">/', $html, $m)) {
            return html_entity_decode($m[1]);
        }

        if (preg_match('/class="brand"[^>]*href="([^"]*)"/', $html, $m)) {
            return html_entity_decode($m[1]);
        }

        return null;
    }


    public function test_logo_di_halaman_presensi_menuju_beranda_siswa(): void
    {
        $siswa = $this->buatAkun('siswa', 'logo_siswa');

        foreach ([
            route('siswa.presensi.index'),
            route('siswa.presensi.riwayat'),
            route('siswa.presensi.pindai'),
        ] as $halaman) {
            $html = $this->actingAs($siswa)->get($halaman)->assertOk()->getContent();

            $this->assertSame(
                route('siswa.beranda'),
                $this->hrefLogo($html),
                "Logo di {$halaman} tidak menuju beranda siswa."
            );
        }
    }


    public function test_logo_di_halaman_pemilihan_menuju_beranda_siswa(): void
    {
        $siswa = $this->buatAkun('siswa', 'logo_pemilihan');

        $html = $this->actingAs($siswa)
            ->get(route('siswa.pemilihan.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(route('siswa.beranda'), $this->hrefLogo($html));
    }


    public function test_logo_di_halaman_kas_dan_denah_menuju_beranda_siswa(): void
    {
        $siswa = $this->buatAkun('siswa', 'logo_kas');
        $this->siapkanKelasUntukSiswa($siswa);

        foreach ([route('kas.index'), route('denah.index')] as $halaman) {
            $html = $this->actingAs($siswa)->get($halaman)->assertOk()->getContent();

            $this->assertSame(
                route('siswa.beranda'),
                $this->hrefLogo($html),
                "Logo di {$halaman} tidak menuju beranda siswa."
            );
        }
    }


    private function siapkanKelasUntukSiswa(User $siswa): void
    {
        $idSiswa = (int) DB::table('siswa')->where('id_user', $siswa->id_user)->value('id_siswa');

        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30', 'is_aktif' => true,
        ]);
        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahun, 'nama_kelas' => 'LOGO', 'tingkat' => 10,
        ]);
        DB::table('anggota_kelas')->insert([
            'id_kelas' => $idKelas, 'id_siswa' => $idSiswa, 'no_absen' => 1,
        ]);
    }


    public function test_logo_di_semua_halaman_guru_menuju_beranda_guru(): void
    {
        $guru = $this->buatAkun('guru', 'logo_guru');

        foreach ([
            route('guru.beranda'),
            route('guru.presensi.index'),
            route('perangkat.index'),
        ] as $halaman) {
            $html = $this->actingAs($guru)->get($halaman)->assertOk()->getContent();

            $this->assertSame(
                route('guru.beranda'),
                $this->hrefLogo($html),
                "Logo di {$halaman} tidak menuju beranda guru."
            );
        }
    }


    public function test_logo_di_halaman_admin_menuju_dashboard_admin(): void
    {
        $admin = $this->buatAkun('admin', 'logo_admin');

        foreach ([
            route('admin.dashboard'),
            route('admin.siswa.index'),
        ] as $halaman) {
            $html = $this->actingAs($admin)->get($halaman)->assertOk()->getContent();

            $this->assertSame(
                route('admin.dashboard'),
                $this->hrefLogo($html),
                "Logo di {$halaman} tidak menuju dashboard admin."
            );
        }
    }


    public function test_logo_di_portal_orang_tua_menuju_portal(): void
    {
        $ortu = $this->buatAkun('orang_tua', 'logo_ortu');

        $html = $this->actingAs($ortu)->get(route('ortu.index'))->assertOk()->getContent();

        $this->assertSame(route('ortu.index'), $this->hrefLogo($html));
    }


    public function test_logo_di_halaman_sesi_guru_menuju_beranda_guru(): void
    {
        $guru = $this->buatAkun('guru', 'logo_sesi');
        $idGuru = (int) DB::table('guru')->where('id_user', $guru->id_user)->value('id_guru');

        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30', 'is_aktif' => true,
        ]);
        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahun, 'nama_kelas' => 'LOGO', 'tingkat' => 10,
        ]);
        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'LGO', 'nama_mapel' => 'Logo',
        ]);
        $idJadwal = DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $idKelas, 'id_mapel' => $idMapel, 'id_guru' => $idGuru,
            'semester' => 'ganjil', 'hari' => 'senin',
            'jam_mulai' => '07:00:00', 'jam_selesai' => '08:00:00',
        ]);
        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal, 'tanggal' => now()->toDateString(),
            'dibuka_oleh' => $idGuru, 'dibuka_pada' => now(), 'status' => 'dibuka',
        ]);

        $html = $this->actingAs($guru)
            ->get(route('guru.presensi.sesi', $idSesi))
            ->assertOk()
            ->getContent();

        $this->assertSame(route('guru.beranda'), $this->hrefLogo($html));
    }


    public function test_logo_untuk_tamu_menuju_halaman_depan(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertSame(url('/'), $this->hrefLogo($html));
    }
}
