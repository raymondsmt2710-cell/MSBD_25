<?php

namespace Tests\Feature\Organisasi;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Data uji untuk fitur perangkat organisasi & kelas.
 *
 * Bentuk array kembalian dipakai oleh SEMUA test fitur ini, jadi
 * kunci-kuncinya tidak boleh berubah.
 */
trait OrganisasiTestSupport
{
    protected static int $organisasiSeedCounter = 0;

    protected array $tablesToTruncate = [
        'pengurus_kelas',
        'pengurus_organisasi',
        'anggota_kelas',
        'jabatan',
        'siswa',
        'kelas',
        'organisasi',
        'tahun_ajaran',
        'guru',
        'users',
    ];

    protected function userDari(int $idUser): User
    {
        return User::findOrFail($idUser);
    }

    /**
     * Siapkan: 1 tahun ajaran, 2 guru (wali kelas & guru lain), 1 organisasi
     * (pembina = wali kelas), 1 kelas, 4 siswa anggota kelas, jabatan standar,
     * dan pengurus awal (ketua + wakil) untuk kelas maupun organisasi.
     */
    protected function seedPerangkat(): array
    {
        $cap = ++self::$organisasiSeedCounter;
        $hash = bcrypt('password');

        $buatUser = fn (string $username, string $role) => DB::connection('mysql')->table('users')->insertGetId([
            'username' => $cap.'_'.$username,
            'password_hash' => $hash,
            'role' => $role,
            'is_active' => true,
        ]);

        $idAdmin = $buatUser('admin', 'admin');
        $idWaliUser = $buatUser('wali', 'guru');
        $idGuruLainUser = $buatUser('guru_lain', 'guru');
        $idSiswaUser = [];
        $idSiswa = [];

        $idTahunAjaran = DB::connection('mysql')->table('tahun_ajaran')->insertGetId([
            'nama' => (2026 + $cap).'/'.(2027 + $cap),
            'tanggal_mulai' => '2026-07-13',
            'tanggal_selesai' => '2027-06-26',
            'is_aktif' => true,
        ]);

        $idWali = DB::connection('mysql')->table('guru')->insertGetId([
            'id_user' => $idWaliUser,
            'nip' => '9000'.$cap.'0001',
            'nama_guru' => 'Wali Kelas '.$cap,
            'jenis_kelamin' => 'L',
        ]);

        $idGuruLain = DB::connection('mysql')->table('guru')->insertGetId([
            'id_user' => $idGuruLainUser,
            'nip' => '9000'.$cap.'0002',
            'nama_guru' => 'Guru Lain '.$cap,
            'jenis_kelamin' => 'P',
        ]);

        $idKelas = DB::connection('mysql')->table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahunAjaran,
            'nama_kelas' => 'X Uji '.$cap,
            'tingkat' => 10,
            'jurusan' => 'IPA',
            'id_wali_kelas' => $idWali,
            'ruangan' => 'R-'.(100 + $cap),
        ]);

        $idOrganisasi = DB::connection('mysql')->table('organisasi')->insertGetId([
            'nama_organisasi' => 'OSIS Uji '.$cap,
            'singkatan' => 'OSIS',
            'deskripsi' => 'Organisasi untuk pengujian.',
            'id_pembina' => $idWali,
        ]);

        foreach ([1 => 'Siswa Satu', 2 => 'Siswa Dua', 3 => 'Siswa Tiga', 4 => 'Siswa Empat'] as $no => $nama) {
            $idUser = $buatUser('siswa'.$no, 'siswa');

            $idSiswa[] = DB::connection('mysql')->table('siswa')->insertGetId([
                'id_user' => $idUser,
                'nis' => '7700'.$cap.'0'.$no,
                'nisn' => '7700'.$cap.'00'.$no,
                'nama_siswa' => $nama.' '.$cap,
                'jenis_kelamin' => $no % 2 ? 'L' : 'P',
                'status' => 'aktif',
            ]);

            $idSiswaUser[] = $idUser;

            DB::connection('mysql')->table('anggota_kelas')->insert([
                'id_kelas' => $idKelas,
                'id_siswa' => end($idSiswa),
                'no_absen' => $no,
            ]);
        }

        // ---------- Jabatan standar ----------
        $jabatanKelas = [
            ['nama_jabatan' => 'Ketua Kelas', 'urutan' => 1],
            ['nama_jabatan' => 'Wakil Ketua', 'urutan' => 2],
            ['nama_jabatan' => 'Sekretaris', 'urutan' => 3],
            ['nama_jabatan' => 'Bendahara', 'urutan' => 4],
        ];

        $idJabatanKelas = [];

        foreach ($jabatanKelas as $j) {
            $idJabatanKelas[] = DB::connection('mysql')->table('jabatan')->insertGetId([
                'nama_jabatan' => $j['nama_jabatan'],
                'lingkup' => 'kelas',
                'kuota' => 1,
                'urutan' => $j['urutan'],
            ]);
        }

        $jabatanOrganisasi = [
            ['nama_jabatan' => 'Ketua OSIS', 'urutan' => 1],
            ['nama_jabatan' => 'Wakil Ketua', 'urutan' => 2],
            ['nama_jabatan' => 'Sekretaris', 'urutan' => 3],
            ['nama_jabatan' => 'Bendahara', 'urutan' => 4],
        ];

        $idJabatanOrganisasi = [];

        foreach ($jabatanOrganisasi as $j) {
            $idJabatanOrganisasi[] = DB::connection('mysql')->table('jabatan')->insertGetId([
                'nama_jabatan' => $j['nama_jabatan'],
                'lingkup' => 'organisasi',
                'kuota' => 1,
                'urutan' => $j['urutan'],
            ]);
        }

        // ---------- Pengurus awal ----------
        DB::connection('mysql')->table('pengurus_kelas')->insert([
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswa[0], 'id_jabatan' => $idJabatanKelas[0]],
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswa[1], 'id_jabatan' => $idJabatanKelas[1]],
        ]);

        DB::connection('mysql')->table('pengurus_organisasi')->insert([
            [
                'id_organisasi' => $idOrganisasi,
                'id_tahun_ajaran' => $idTahunAjaran,
                'id_siswa' => $idSiswa[2],
                'id_jabatan' => $idJabatanOrganisasi[0],
                'bidang' => null,
            ],
        ]);

        return [
            'id_admin' => $idAdmin,
            'id_wali_user' => $idWaliUser,
            'id_guru_lain_user' => $idGuruLainUser,
            'id_wali' => $idWali,
            'id_guru_lain' => $idGuruLain,
            'id_kelas' => $idKelas,
            'id_organisasi' => $idOrganisasi,
            'id_tahun_ajaran' => $idTahunAjaran,
            'id_siswa' => $idSiswa,
            'id_siswa_user' => $idSiswaUser,
            'id_jabatan_kelas' => $idJabatanKelas,
            'id_jabatan_organisasi' => $idJabatanOrganisasi,
        ];
    }
}
