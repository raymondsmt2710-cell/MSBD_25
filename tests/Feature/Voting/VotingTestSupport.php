<?php

namespace Tests\Feature\Voting;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait VotingTestSupport
{

    protected static int $seedCounter = 0;


    protected function setUpVotingDatabase(): void
    {
        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL tidak tersedia untuk test pemilihan: '.$e->getMessage());
        }
    }


    protected array $tablesToTruncate = [
        'suara',
        'daftar_pemilih',
        'panitia_pemilihan',
        'kandidat',
        'pemilihan',
        'log_pemilihan',
        'anggota_kelas',
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


    protected function seedPemilihanBerlangsung(): array
    {
        return $this->seedPemilihanDasar(
            status: 'berlangsung',
            mulai: now()->subHour(),
            selesai: now()->addHour(),
        );
    }


    protected function seedPemilihanDraf(): array
    {
        return $this->seedPemilihanDasar(
            status: 'draf',
            mulai: now()->addHour(),
            selesai: now()->addDay(),
        );
    }


    private function seedPemilihanDasar(string $status, $mulai, $selesai): array
    {
        $hash = bcrypt('password');



        $cap = 'uji_'.(++self::$seedCounter).'_';

        $buatUser = fn (string $username, string $role) => DB::table('users')->insertGetId([
            'username' => $cap.$username,
            'password_hash' => $hash,
            'role' => $role,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $idAdmin = $buatUser('admin', 'admin');
        $idPanitia = $buatUser('panitia', 'guru');
        $idGuruLain = $buatUser('guru_lain', 'guru');

        $idTahunAjaran = DB::table('tahun_ajaran')->insertGetId([
            'nama' => (2026 + self::$seedCounter).'/'.(2027 + self::$seedCounter),
            'tanggal_mulai' => '2026-07-13',
            'tanggal_selesai' => '2027-06-26',
            'is_aktif' => true,
        ]);

        $idGuru = DB::table('guru')->insertGetId([
            'id_user' => $idPanitia,
            'nip' => substr(str_pad((string) self::$seedCounter, 6, '0', STR_PAD_LEFT).'198001012005011001', 0, 18),
            'nama_guru' => 'Guru Uji',
            'jenis_kelamin' => 'L',
        ]);

        $idOrganisasi = DB::table('organisasi')->insertGetId([
            'nama_organisasi' => substr('OSIS Uji '.$cap, 0, 100),
            'singkatan' => 'OSIS',
            'deskripsi' => 'Organisasi uji',
            'id_pembina' => $idGuru,
        ]);

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahunAjaran,
            'nama_kelas' => 'X IPA 1',
            'tingkat' => 10,
            'jurusan' => 'IPA',
            'id_wali_kelas' => $idGuru,
            'ruangan' => 'R-101',
        ]);

        $idSiswa = [];
        $idSiswaUser = [];

        foreach ([1 => 'Siswa Satu', 2 => 'Siswa Dua'] as $no => $nama) {
            $idUser = $buatUser("uji_siswa{$no}", 'siswa');

            $idSiswa[] = DB::table('siswa')->insertGetId([
                'id_user' => $idUser,
                'nis' => substr('2'.str_pad((string) self::$seedCounter, 3, '0', STR_PAD_LEFT).'00'.$no, 0, 20),
                'nisn' => substr(str_pad((string) self::$seedCounter, 4, '0', STR_PAD_LEFT).'0000'.$no, 0, 10),
                'nama_siswa' => $nama,
                'jenis_kelamin' => $no % 2 ? 'L' : 'P',
                'status' => 'aktif',
            ]);

            $idSiswaUser[] = $idUser;

            DB::table('anggota_kelas')->insert([
                'id_kelas' => $idKelas,
                'id_siswa' => end($idSiswa),
                'no_absen' => $no,
            ]);
        }

        $idPemilihan = DB::table('pemilihan')->insertGetId([
            'id_organisasi' => $idOrganisasi,
            'id_tahun_ajaran' => $idTahunAjaran,
            'judul' => 'Pemilihan Ketua OSIS Uji',
            'deskripsi' => 'Pemilihan untuk keperluan pengujian.',
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $selesai,


            'status' => 'draf',
            'dibuat_oleh' => $idAdmin,
        ]);

        $idKandidat = [];

        foreach ([1 => $idSiswa[0], 2 => $idSiswa[1]] as $nomor => $idKetua) {
            $idKandidat[] = DB::table('kandidat')->insertGetId([
                'id_pemilihan' => $idPemilihan,
                'nomor_urut' => $nomor,
                'id_siswa_ketua' => $idKetua,
                'id_siswa_wakil' => null,
                'visi' => 'Visi kandidat '.$nomor,
                'misi' => 'Misi kandidat '.$nomor,
                'foto_url' => null,
            ]);
        }

        foreach ($idSiswa as $id) {
            DB::table('daftar_pemilih')->insert([
                'id_pemilihan' => $idPemilihan,
                'id_siswa' => $id,
                'sudah_memilih' => false,
                'waktu_memilih' => null,
            ]);
        }



        if ($status !== 'draf') {
            DB::table('pemilihan')
                ->where('id_pemilihan', $idPemilihan)
                ->update(['status' => $status]);
        }

        return [
            'id_pemilihan' => $idPemilihan,
            'id_kandidat' => $idKandidat,
            'id_siswa' => $idSiswa,
            'id_siswa_user' => $idSiswaUser,
            'id_admin' => $idAdmin,
            'id_panitia_user' => $idPanitia,
            'id_guru_lain' => $idGuruLain,
            'id_kelas' => $idKelas,
            'id_organisasi' => $idOrganisasi,
            'id_tahun_ajaran' => $idTahunAjaran,
        ];
    }
}
