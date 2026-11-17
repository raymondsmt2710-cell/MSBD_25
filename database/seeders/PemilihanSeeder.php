<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh fitur pemilihan.
 *
 * Idempoten: memakai pengecekan keberadaan, jadi aman dijalankan berulang
 * tanpa menggandakan data.
 *
 * Semua password akun contoh: "password"
 */
class PemilihanSeeder extends Seeder
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
        $idGuruUser = $buatUser('guru1', 'guru');

        // ---------- Tahun ajaran ----------

        $idTahunAjaran = DB::table('tahun_ajaran')->where('nama', '2026/2027')->value('id_tahun_ajaran')
            ?? DB::table('tahun_ajaran')->insertGetId([
                'nama' => '2026/2027',
                'tanggal_mulai' => '2026-07-13',
                'tanggal_selesai' => '2027-06-26',
                'is_aktif' => true,
            ]);

        // ---------- Guru & organisasi ----------

        $idGuru = DB::table('guru')->where('id_user', $idGuruUser)->value('id_guru')
            ?? DB::table('guru')->insertGetId([
                'id_user' => $idGuruUser,
                'nip' => '198001012005011001',
                'nama_guru' => 'Bapak Contoh Guru',
                'jenis_kelamin' => 'L',
            ]);

        $idOrganisasi = DB::table('organisasi')->where('nama_organisasi', 'OSIS')->value('id_organisasi')
            ?? DB::table('organisasi')->insertGetId([
                'nama_organisasi' => 'OSIS',
                'singkatan' => 'OSIS',
                'deskripsi' => 'Organisasi Siswa Intra Sekolah SMA Negeri 1 Kabanjahe.',
                'id_pembina' => $idGuru,
            ]);

        // ---------- Kelas & siswa ----------

        $kelas = [
            ['nama_kelas' => 'X IPA 1', 'tingkat' => 10, 'jurusan' => 'IPA', 'ruangan' => 'R-101'],
            ['nama_kelas' => 'XI IPA 1', 'tingkat' => 11, 'jurusan' => 'IPA', 'ruangan' => 'R-201'],
            ['nama_kelas' => 'XII IPA 1', 'tingkat' => 12, 'jurusan' => 'IPA', 'ruangan' => 'R-301'],
        ];

        $idKelas = [];

        foreach ($kelas as $k) {
            $idKelas[] = DB::table('kelas')
                ->where('id_tahun_ajaran', $idTahunAjaran)
                ->where('nama_kelas', $k['nama_kelas'])
                ->value('id_kelas')
                ?? DB::table('kelas')->insertGetId($k + [
                    'id_tahun_ajaran' => $idTahunAjaran,
                    'id_wali_kelas' => $idGuru,
                ]);
        }

        $namaSiswa = [
            'Aditya Pratama', 'Bunga Lestari', 'Chandra Sitepu',
            'Dewi Simanjuntak', 'Eko Ginting', 'Fitri Sembiring',
            'Gilang Tarigan', 'Hana Sinaga', 'Ivan Karo',
        ];

        $idSiswa = [];

        foreach ($namaSiswa as $i => $nama) {
            $no = $i + 1;
            $idUser = $buatUser('siswa'.$no, 'siswa');

            $id = DB::table('siswa')->where('id_user', $idUser)->value('id_siswa');

            if (! $id) {
                $id = DB::table('siswa')->insertGetId([
                    'id_user' => $idUser,
                    'nis' => '2026000'.$no,
                    'nisn' => '00260000'.str_pad((string) $no, 2, '0', STR_PAD_LEFT),
                    'nama_siswa' => $nama,
                    'jenis_kelamin' => $no % 2 ? 'L' : 'P',
                    'status' => 'aktif',
                ]);
            }

            $idSiswa[] = $id;

            // Tiga siswa per kelas.
            $idKelasSiswa = $idKelas[intdiv($i, 3)];

            $sudahAnggota = DB::table('anggota_kelas')
                ->where('id_kelas', $idKelasSiswa)->where('id_siswa', $id)->exists();

            if (! $sudahAnggota) {
                DB::table('anggota_kelas')->insert([
                    'id_kelas' => $idKelasSiswa,
                    'id_siswa' => $id,
                    'no_absen' => ($i % 3) + 1,
                ]);
            }
        }

        // ---------- Pemilihan contoh ----------

        $idPemilihan = DB::table('pemilihan')
            ->where('judul', 'Pemilihan Ketua OSIS 2026/2027')
            ->value('id_pemilihan');

        if (! $idPemilihan) {
            // Dibuat sebagai draf dulu, karena trigger database menolak penambahan
            // kandidat/pemilih pada pemilihan yang sudah dibuka.
            $idPemilihan = DB::table('pemilihan')->insertGetId([
                'id_organisasi' => $idOrganisasi,
                'id_tahun_ajaran' => $idTahunAjaran,
                'judul' => 'Pemilihan Ketua OSIS 2026/2027',
                'deskripsi' => 'Pemilihan Ketua dan Wakil Ketua OSIS periode 2026/2027. '
                    .'Pilih satu pasangan calon. Suara Anda rahasia.',
                'waktu_mulai' => $now->copy()->subHour(),
                'waktu_selesai' => $now->copy()->addDays(7),
                'status' => 'draf',
                'dibuat_oleh' => DB::table('users')->where('username', 'admin')->value('id_user'),
            ]);
        }

        // ---------- Kandidat ----------

        $kandidat = [
            ['nomor' => 1, 'ketua' => 0, 'wakil' => 1, 'visi' => 'OSIS yang aktif, kreatif, dan dekat dengan siswa.',
             'misi' => 'Menambah kegiatan ekstrakurikuler; membuka forum aspirasi siswa setiap bulan.'],
            ['nomor' => 2, 'ketua' => 3, 'wakil' => 4, 'visi' => 'Mewujudkan OSIS yang berprestasi dan berkarakter.',
             'misi' => 'Mengadakan kelas belajar bersama; mengadakan lomba antarkelas tiap semester.'],
            ['nomor' => 3, 'ketua' => 6, 'wakil' => 7, 'visi' => 'Sekolah hijau, siswa sehat, dan saling peduli.',
             'misi' => 'Gerakan sekolah bersih setiap Jumat; bank sampah dan taman baca kelas.'],
        ];

        foreach ($kandidat as $k) {
            $sudahAda = DB::table('kandidat')
                ->where('id_pemilihan', $idPemilihan)
                ->where('nomor_urut', $k['nomor'])
                ->exists();

            if (! $sudahAda) {
                DB::table('kandidat')->insert([
                    'id_pemilihan' => $idPemilihan,
                    'nomor_urut' => $k['nomor'],
                    'id_siswa_ketua' => $idSiswa[$k['ketua']],
                    'id_siswa_wakil' => $idSiswa[$k['wakil']],
                    'visi' => $k['visi'],
                    'misi' => $k['misi'],
                    'foto_url' => null,
                ]);
            }
        }

        // ---------- Daftar pemilih ----------

        foreach ($idSiswa as $id) {
            $terdaftar = DB::table('daftar_pemilih')
                ->where('id_pemilihan', $idPemilihan)->where('id_siswa', $id)->exists();

            if (! $terdaftar) {
                DB::table('daftar_pemilih')->insert([
                    'id_pemilihan' => $idPemilihan,
                    'id_siswa' => $id,
                    'sudah_memilih' => false,
                    'waktu_memilih' => null,
                ]);
            }
        }

        // ---------- Panitia (guru1) ----------

        $idGuruUserSudah = DB::table('users')->where('username', 'guru1')->value('id_user');

        $sudahPanitia = DB::table('panitia_pemilihan')
            ->where('id_pemilihan', $idPemilihan)->where('id_user', $idGuruUserSudah)->exists();

        if (! $sudahPanitia) {
            DB::table('panitia_pemilihan')->insert([
                'id_pemilihan' => $idPemilihan,
                'id_user' => $idGuruUserSudah,
            ]);
        }

        // Terakhir: buka pemilihan agar siap dicoba. Hanya transisi draf -> berlangsung
        // yang diizinkan trigger, dan hanya jika belum pernah dibuka.
        $statusSekarang = DB::table('pemilihan')
            ->where('id_pemilihan', $idPemilihan)->value('status');

        if ($statusSekarang === 'draf') {
            DB::table('pemilihan')
                ->where('id_pemilihan', $idPemilihan)
                ->update(['status' => 'berlangsung']);
        }
    }
}
