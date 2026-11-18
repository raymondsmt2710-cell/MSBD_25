<?php

namespace Tests\Feature;

use Database\Seeders\OrangTuaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Memastikan OrangTuaSeeder benar-benar membuat akun orang tua, menghubungkannya ke
// anak, dan aman dijalankan berulang (idempoten).
//
// Ini penting karena seeder adalah SATU-SATUNYA sumber akun berperan orang_tua:
// DatabaseSeeder tidak membuatnya, sehingga tanpa seeder ini portal tidak bisa dipakai.
class OrangTuaSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Menyiapkan rantai FK minimal: guru -> kelas -> mapel -> jadwal, lalu 2 siswa. */
    private function siapkanDataDasar(): array
    {
        $idGuruUser = DB::table('users')->insertGetId([
            'username' => 'guru_seed', 'password_hash' => Hash::make('password'),
            'role' => 'guru', 'is_active' => true,
        ]);

        $idGuru = DB::table('guru')->insertGetId([
            'id_user' => $idGuruUser, 'nip' => '198001012005011001',
            'nama_guru' => 'Guru Seed', 'jenis_kelamin' => 'L',
        ]);

        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-13',
            'tanggal_selesai' => '2027-06-26', 'is_aktif' => true,
        ]);

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahun, 'nama_kelas' => 'X IPA 1',
            'tingkat' => 10, 'jurusan' => 'IPA', 'id_wali_kelas' => $idGuru,
        ]);

        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'MTK', 'nama_mapel' => 'Matematika',
        ]);

        DB::table('jadwal_pelajaran')->insert([
            'id_kelas' => $idKelas, 'id_mapel' => $idMapel, 'id_guru' => $idGuru,
            'semester' => 'ganjil', 'hari' => 'senin',
            'jam_mulai' => '07:30:00', 'jam_selesai' => '09:00:00',
        ]);

        $idSiswa = [];

        foreach ([1, 2, 3] as $no) {
            $idUserSiswa = DB::table('users')->insertGetId([
                'username' => 'siswa_seed'.$no, 'password_hash' => Hash::make('password'),
                'role' => 'siswa', 'is_active' => true,
            ]);

            $idSiswa[] = DB::table('siswa')->insertGetId([
                'id_user' => $idUserSiswa,
                'nis' => '2026000'.$no,
                'nisn' => '002600000'.$no,
                'nama_siswa' => 'Siswa Seed '.$no,
                'jenis_kelamin' => 'L',
                'status' => 'aktif',
            ]);
        }

        return $idSiswa;
    }

    public function test_seeder_membuat_akun_orang_tua_dan_menghubungkan_dua_anak(): void
    {
        $this->siapkanDataDasar();

        (new OrangTuaSeeder())->run();

        $user = DB::table('users')->where('username', 'ortu1')->first();
        $this->assertNotNull($user, 'Akun ortu1 tidak dibuat.');
        $this->assertSame('orang_tua', $user->role);
        $this->assertTrue((bool) $user->is_active);

        // Password harus benar-benar bisa dipakai login.
        $this->assertTrue(Hash::check('password', $user->password_hash));

        $orangTua = DB::table('orang_tua')->where('id_user', $user->id_user)->first();
        $this->assertNotNull($orangTua, 'Baris orang_tua tidak dibuat.');

        $anak = DB::table('orang_tua_siswa')
            ->where('id_orang_tua', $orangTua->id_orang_tua)
            ->pluck('id_siswa');

        $this->assertCount(2, $anak, 'Orang tua harus terhubung ke 2 anak.');
    }

    public function test_seeder_idempoten_tidak_menggandakan_data(): void
    {
        $this->siapkanDataDasar();

        (new OrangTuaSeeder())->run();
        (new OrangTuaSeeder())->run();

        $this->assertSame(1, DB::table('users')->where('username', 'ortu1')->count());
        $this->assertSame(1, DB::table('orang_tua')->count());
        $this->assertSame(2, DB::table('orang_tua_siswa')->count());
        $this->assertSame(3, DB::table('sesi_presensi')->count());
        $this->assertSame(6, DB::table('presensi')->count());
    }

    public function test_seeder_mengisi_presensi_contoh_yang_menghasilkan_persentase_berbeda(): void
    {
        $idSiswa = $this->siapkanDataDasar();

        (new OrangTuaSeeder())->run();

        // Pola: anak 1 -> hadir,hadir,izin (2/3); anak 2 -> hadir,alpa,sakit (1/3).
        $hadir1 = DB::table('presensi')
            ->where('id_siswa', $idSiswa[0])->where('status', 'hadir')->count();
        $hadir2 = DB::table('presensi')
            ->where('id_siswa', $idSiswa[1])->where('status', 'hadir')->count();

        $this->assertSame(2, $hadir1);
        $this->assertSame(1, $hadir2);
    }

    public function test_seeder_tidak_menimpa_presensi_yang_sudah_ada(): void
    {
        $idSiswa = $this->siapkanDataDasar();

        // Anggap fitur presensi sudah punya data sendiri.
        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => DB::table('jadwal_pelajaran')->value('id_jadwal'),
            'tanggal' => '2026-11-01', 'status' => 'ditutup',
        ]);
        DB::table('presensi')->insert([
            'id_sesi' => $idSesi, 'id_siswa' => $idSiswa[0],
            'status' => 'hadir', 'metode' => 'manual',
        ]);

        (new OrangTuaSeeder())->run();

        // Presensi contoh tidak boleh ditambahkan karena sudah ada data.
        $this->assertSame(1, DB::table('presensi')->count());
        $this->assertSame(1, DB::table('sesi_presensi')->count());
    }

    public function test_seeder_tetap_membuat_akun_walau_belum_ada_siswa(): void
    {
        (new OrangTuaSeeder())->run();

        $this->assertSame(1, DB::table('users')->where('username', 'ortu1')->count());
        $this->assertSame(1, DB::table('orang_tua')->count());
        $this->assertSame(0, DB::table('orang_tua_siswa')->count());
    }
}
