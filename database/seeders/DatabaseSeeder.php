<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal untuk menguji fitur presensi. Semua password: "password".
 *
 * IDEMPOTEN: aman dijalankan berulang kali maupun di database yang sudah berisi
 * data (mis. setelah `php artisan migrate --seed` pada project yang sudah jalan).
 * Setiap baris dibuat dengan pola "cari dulu, buat bila belum ada", sehingga
 * `php artisan migrate --seed` tidak gagal dengan "Duplicate entry".
 *
 * Untuk data demo yang lebih lengkap (kas, denah, pengurus, pemilihan),
 * jalankan DemoSekolahSeeder secara terpisah.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $hash = Hash::make('password');
        $now = now();

        // ---------- Akun dasar ----------

        $buatUser = function (string $username, string $role) use ($hash, $now): int {
            $ada = DB::table('users')->where('username', $username)->value('id_user');

            return $ada ?? DB::table('users')->insertGetId([
                'username' => $username,
                'password_hash' => $hash,
                'role' => $role,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $buatUser('admin', 'admin');

        // ---------- Tahun ajaran ----------

        $idTahun = DB::table('tahun_ajaran')->where('nama', '2026/2027')->value('id_tahun_ajaran')
            ?? DB::table('tahun_ajaran')->insertGetId([
                'nama' => '2026/2027',
                'tanggal_mulai' => '2026-07-13',
                'tanggal_selesai' => '2027-06-26',
                'is_aktif' => true,
            ]);

        // ---------- Guru ----------

        $idGuruUser = $buatUser('guru1', 'guru');

        $idGuru = DB::table('guru')->where('id_user', $idGuruUser)->value('id_guru')
            ?? DB::table('guru')->insertGetId([
                'id_user' => $idGuruUser,
                'nip' => '198001012005011001',
                'nama_guru' => 'Bapak Contoh Guru',
                'jenis_kelamin' => 'L',
            ]);

        $idGuruUser = $buatUser('guru2', 'guru');

        $idGuru = DB::table('guru')->where('id_user', $idGuruUser)->value('id_guru')
            ?? DB::table('guru')->insertGetId([
                'id_user' => $idGuruUser,
                'nip' => '198001012005011002',
                'nama_guru' => 'Bapak Contoh Guru 2',
                'jenis_kelamin' => 'P',
            ]);

        // ---------- Kelas ----------

        $idKelas = DB::table('kelas')
            ->where('id_tahun_ajaran', $idTahun)
            ->where('nama_kelas', 'X IPA 1')
            ->value('id_kelas')
            ?? DB::table('kelas')->insertGetId([
                'id_tahun_ajaran' => $idTahun,
                'nama_kelas' => 'X IPA 1',
                'tingkat' => 10,
                'jurusan' => 'IPA',
                'id_wali_kelas' => $idGuru,
                'ruangan' => 'R-101',
            ]);

        // ---------- Mata pelajaran ----------

        $idMapel = DB::table('mata_pelajaran')->where('kode_mapel', 'MTK')->value('id_mapel')
            ?? DB::table('mata_pelajaran')->insertGetId([
                'kode_mapel' => 'MTK',
                'nama_mapel' => 'Matematika',
            ]);

        // ---------- Siswa + anggota kelas ----------

        foreach ([1 => 'Siswa Satu', 2 => 'Siswa Dua', 3 => 'Siswa Tiga'] as $no => $nama) {
            $idSiswaUser = $buatUser("siswa{$no}", 'siswa');

            $idSiswa = DB::table('siswa')->where('id_user', $idSiswaUser)->value('id_siswa')
                ?? DB::table('siswa')->insertGetId([
                    'id_user' => $idSiswaUser,
                    'nis' => '2026000' . $no,
                    'nisn' => '00260000' . str_pad((string) $no, 2, '0', STR_PAD_LEFT),
                    'nama_siswa' => $nama,
                    'jenis_kelamin' => $no % 2 ? 'L' : 'P',
                    'status' => 'aktif',
                ]);

            $sudahAnggota = DB::table('anggota_kelas')
                ->where('id_kelas', $idKelas)
                ->where('id_siswa', $idSiswa)
                ->exists();

            if (! $sudahAnggota) {
                DB::table('anggota_kelas')->insert([
                    'id_kelas' => $idKelas,
                    'id_siswa' => $idSiswa,
                    'no_absen' => $no,
                ]);
            }
        }

        // ---------- Jadwal ----------
        // SENGAJA TIDAK dibuat di sini.
        //
        // Versi sebelumnya membuat satu jadwal "hari ini" supaya tombol "Buat
        // Presensi" langsung bisa dicoba. Akibatnya roster guru terisi satu baris
        // tanpa guru pernah membuatnya, dan baris itu muncul di hari yang salah
        // (mis. jadwal "Senin" terbentuk saat seeder dijalankan hari Kamis).
        //
        // Jadwal sekarang hanya dibuat lewat panel admin (/admin/jadwal) supaya
        // data yang tampil selalu berasal dari keputusan pengguna.

        // ---------- Seeder fitur lain ----------

        // PemilihanSeeder sudah idempoten (memakai pola "cari dulu, buat bila belum ada").
        $this->call(PemilihanSeeder::class);

        // Akun orang tua untuk fitur 7 (pemantauan wali murid). Idempoten, dan
        // dilewati otomatis bila belum ada data siswa.
        $this->call(OrangTuaSeeder::class);
    }
}
