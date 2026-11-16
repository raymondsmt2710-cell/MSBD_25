<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Data awal untuk menguji fitur presensi. Semua password: "password"
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $hash = Hash::make('password');
        $now = now();

        $user = fn (string $username, string $role) => DB::table('users')->insertGetId([
            'username' => $username, 'password_hash' => $hash, 'role' => $role,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $user('admin', 'admin');

        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-13', 'tanggal_selesai' => '2027-06-26', 'is_aktif' => true,
        ]);

        $idGuru = DB::table('guru')->insertGetId([
            'id_user' => $user('guru1', 'guru'), 'nip' => '198001012005011001',
            'nama_guru' => 'Bapak Contoh Guru', 'jenis_kelamin' => 'L',
        ]);

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahun, 'nama_kelas' => 'X IPA 1', 'tingkat' => 10,
            'jurusan' => 'IPA', 'id_wali_kelas' => $idGuru, 'ruangan' => 'R-101',
        ]);

        $idMapel = DB::table('mata_pelajaran')->insertGetId(['kode_mapel' => 'MTK', 'nama_mapel' => 'Matematika']);

        foreach ([1 => 'Siswa Satu', 2 => 'Siswa Dua', 3 => 'Siswa Tiga'] as $no => $nama) {
            $idSiswa = DB::table('siswa')->insertGetId([
                'id_user' => $user("siswa{$no}", 'siswa'),
                'nis' => '2026000'.$no, 'nisn' => '00260000'.str_pad((string) $no, 2, '0', STR_PAD_LEFT),
                'nama_siswa' => $nama, 'jenis_kelamin' => $no % 2 ? 'L' : 'P', 'status' => 'aktif',
            ]);
            DB::table('anggota_kelas')->insert(['id_kelas' => $idKelas, 'id_siswa' => $idSiswa, 'no_absen' => $no]);
        }

        // Jadwal diset pada hari ini agar langsung bisa dicoba (hari Minggu dianggap Senin)
        $hari = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'][$now->dayOfWeek] ?? 'senin';
        DB::table('jadwal_pelajaran')->insert([
            'id_kelas' => $idKelas, 'id_mapel' => $idMapel, 'id_guru' => $idGuru,
            'semester' => 'ganjil', 'hari' => $hari, 'jam_mulai' => '07:30:00', 'jam_selesai' => '09:00:00',
        ]);
    }
}
