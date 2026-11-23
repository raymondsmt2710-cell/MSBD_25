<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PresensiBatasJamTest extends TestCase
{
    use DatabaseTruncation;

    private function buatGuru(string $username = 'jam_guru'): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        DB::table('guru')->insert([
            'id_user' => $idUser,
            'nip' => '1987'.$idUser,
            'nama_guru' => 'Guru Jam',
            'jenis_kelamin' => 'L',
        ]);

        return User::find($idUser);
    }

    private function buatSiswa(string $username = 'jam_siswa'): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        DB::table('siswa')->insert([
            'id_user' => $idUser,
            'nis' => '2026'.$idUser,
            'nisn' => '002026'.$idUser,
            'nama_siswa' => 'Siswa Jam',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        return User::find($idUser);
    }


    private function hariIni(): string
    {
        return ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'][now()->dayOfWeek - 1] ?? 'senin';
    }


    private function siapkanJadwal(string $jamMulai, string $jamSelesai): array
    {
        $guru = $this->buatGuru('jam_guru_'.uniqid());
        $idGuru = (int) DB::table('guru')->where('id_user', $guru->id_user)->value('id_guru');

        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30', 'is_aktif' => true,
        ]);
        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahun, 'nama_kelas' => 'JAM', 'tingkat' => 10,
        ]);
        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'JAM', 'nama_mapel' => 'Jam',
        ]);
        $idJadwal = DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $idKelas, 'id_mapel' => $idMapel, 'id_guru' => $idGuru,
            'semester' => 'ganjil', 'hari' => $this->hariIni(),
            'jam_mulai' => $jamMulai, 'jam_selesai' => $jamSelesai,
        ]);

        $siswa = $this->buatSiswa('jam_siswa_'.uniqid());
        $idSiswa = (int) DB::table('siswa')->where('id_user', $siswa->id_user)->value('id_siswa');
        DB::table('anggota_kelas')->insert([
            'id_kelas' => $idKelas, 'id_siswa' => $idSiswa, 'no_absen' => 1,
        ]);

        return compact('idGuru', 'idKelas', 'idJadwal', 'idSiswa') + ['idUserGuru' => $guru->id_user];
    }


    private function panggilBukaSesi(int $idJadwal, int $idGuru): array
    {
        $k = DB::connection('mysql');
        $k->statement('SET @st = NULL, @id = NULL');
        $k->statement('CALL sp_buka_sesi(?, ?, @st, @id)', [$idJadwal, $idGuru]);
        $r = $k->selectOne('SELECT @st AS st, @id AS id');

        return [$r->st, $r->id];
    }


    private function panggilScan(string $kode, int $idSiswa): string
    {
        $k = DB::connection('mysql');
        $k->statement('SET @st = NULL');
        $k->statement('CALL sp_scan_presensi(?, ?, NULL, NULL, NULL, @st)', [$kode, $idSiswa]);

        return (string) $k->selectOne('SELECT @st AS st')->st;
    }





    public function test_sesi_tidak_bisa_dibuka_setelah_jam_selesai(): void
    {

        $d = $this->siapkanJadwal('00:01:00', '00:02:00');

        [$status, $idSesi] = $this->panggilBukaSesi($d['idJadwal'], $d['idGuru']);

        $this->assertSame('DI_LUAR_JAM', $status, 'Sesi tidak boleh dibuka setelah jam pelajaran berakhir.');
        $this->assertNull($idSesi);
    }

    public function test_sesi_tidak_bisa_dibuka_lebih_dari_15_menit_sebelum_jam_mulai(): void
    {


        if (now()->format('H:i') >= '22:45') {
            $this->markTestSkipped('Dijalankan terlalu dekat dengan 23:00.');
        }

        $d = $this->siapkanJadwal('23:00:00', '23:59:00');

        [$status, $idSesi] = $this->panggilBukaSesi($d['idJadwal'], $d['idGuru']);

        $this->assertSame('TERLALU_AWAL', $status);
        $this->assertNull($idSesi);
    }

    public function test_sesi_bisa_dibuka_saat_jam_pelajaran_berlangsung(): void
    {

        $d = $this->siapkanJadwal(
            now()->copy()->subMinutes(10)->format('H:i:s'),
            now()->copy()->addMinutes(50)->format('H:i:s')
        );

        [$status, $idSesi] = $this->panggilBukaSesi($d['idJadwal'], $d['idGuru']);

        $this->assertSame('BERHASIL', $status);
        $this->assertNotNull($idSesi);
    }

    public function test_sesi_bisa_dibuka_15_menit_sebelum_jam_mulai(): void
    {

        $d = $this->siapkanJadwal(
            now()->copy()->addMinutes(10)->format('H:i:s'),
            now()->copy()->addMinutes(70)->format('H:i:s')
        );

        [$status, $idSesi] = $this->panggilBukaSesi($d['idJadwal'], $d['idGuru']);

        $this->assertSame('BERHASIL', $status, 'Guru harus bisa membuka sesi 15 menit sebelum jam mulai.');
        $this->assertNotNull($idSesi);
    }





    public function test_qr_belum_berlaku_sebelum_jam_pelajaran(): void
    {


        $d = $this->siapkanJadwal(
            now()->copy()->addMinutes(10)->format('H:i:s'),
            now()->copy()->addMinutes(70)->format('H:i:s')
        );

        [$status, $idSesi] = $this->panggilBukaSesi($d['idJadwal'], $d['idGuru']);
        $this->assertSame('BERHASIL', $status);


        $kode = str_repeat('a', 64);
        DB::table('token_presensi')->insert([
            'id_sesi' => $idSesi,
            'kode_token' => $kode,
            'berlaku_mulai' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
            'dibuat_pada' => now(),
            'kedaluwarsa_pada' => now()->addMinutes(70)->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame('TOKEN_BELUM_BERLAKU', $this->panggilScan($kode, $d['idSiswa']));
    }

    public function test_qr_berlaku_setelah_jam_pelajaran_dimulai(): void
    {
        $d = $this->siapkanJadwal(
            now()->copy()->subMinutes(10)->format('H:i:s'),
            now()->copy()->addMinutes(50)->format('H:i:s')
        );

        [$status, $idSesi] = $this->panggilBukaSesi($d['idJadwal'], $d['idGuru']);
        $this->assertSame('BERHASIL', $status);

        $kode = str_repeat('b', 64);
        DB::table('token_presensi')->insert([
            'id_sesi' => $idSesi,
            'kode_token' => $kode,
            'berlaku_mulai' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
            'dibuat_pada' => now(),
            'kedaluwarsa_pada' => now()->addMinutes(50)->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame('BERHASIL', $this->panggilScan($kode, $d['idSiswa']));
    }


    public function test_fn_token_valid_menghormati_batas_bawah(): void
    {
        $d = $this->siapkanJadwal(
            now()->copy()->addMinutes(10)->format('H:i:s'),
            now()->copy()->addMinutes(70)->format('H:i:s')
        );

        [$status, $idSesi] = $this->panggilBukaSesi($d['idJadwal'], $d['idGuru']);
        $this->assertSame('BERHASIL', $status);

        $kode = str_repeat('c', 64);
        DB::table('token_presensi')->insert([
            'id_sesi' => $idSesi,
            'kode_token' => $kode,
            'berlaku_mulai' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
            'dibuat_pada' => now(),
            'kedaluwarsa_pada' => now()->addMinutes(70)->format('Y-m-d H:i:s'),
        ]);

        $k = DB::connection('mysql');
        $sebelum = $k->selectOne('SELECT fn_token_valid(?) AS ok', [$kode]);
        $this->assertSame(0, (int) $sebelum->ok, 'Token belum boleh dianggap valid sebelum jam pelajaran.');


        DB::table('token_presensi')->where('kode_token', $kode)
            ->update(['berlaku_mulai' => now()->subMinute()->format('Y-m-d H:i:s')]);

        $sesudah = $k->selectOne('SELECT fn_token_valid(?) AS ok', [$kode]);
        $this->assertSame(1, (int) $sesudah->ok);
    }






    public function test_tombol_buat_presensi_tidak_muncul_di_luar_jam(): void
    {
        $d = $this->siapkanJadwal('00:01:00', '00:02:00');
        $guru = User::find($d['idUserGuru']);

        $html = $this->actingAs($guru)
            ->withHeader('referer', route('guru.beranda'))
            ->get(route('guru.presensi.index'))
            ->assertOk()
            ->assertSee('Di luar jam buka')
            ->getContent();

        $this->assertStringNotContainsString(
            'type="submit">Buat Presensi',
            $html,
            'Tombol buat presensi tidak boleh dirender di luar jendela buka.'
        );
    }

    public function test_tombol_buat_presensi_muncul_saat_jam_pelajaran(): void
    {
        $d = $this->siapkanJadwal(
            now()->copy()->subMinutes(10)->format('H:i:s'),
            now()->copy()->addMinutes(50)->format('H:i:s')
        );
        $guru = User::find($d['idUserGuru']);

        $html = $this->actingAs($guru)
            ->withHeader('referer', route('guru.beranda'))
            ->get(route('guru.presensi.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('type="submit">Buat Presensi', $html);
    }
}
