<?php

namespace Tests\Support;

use App\Enums\Role;
use App\Models\AnggotaKelas;
use App\Models\Jabatan;
use App\Models\Kelas;
use App\Models\PengurusKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;

trait MembuatSekolah
{

    protected function buatSekolah(): array
    {
        $tahun = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);

        $kelas = Kelas::create([
            'id_tahun_ajaran' => $tahun->id_tahun_ajaran,
            'nama_kelas' => 'XI IPA 1',
            'tingkat' => 11,
            'jurusan' => 'IPA',
            'ruangan' => 'R-101',
        ]);

        $kelasLain = Kelas::create([
            'id_tahun_ajaran' => $tahun->id_tahun_ajaran,
            'nama_kelas' => 'XI IPA 2',
            'tingkat' => 11,
            'jurusan' => 'IPA',
            'ruangan' => 'R-102',
        ]);

        $admin = User::create([
            'username' => 'admin',
            'password_hash' => 'password',
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $jabatanKetua = Jabatan::create(['nama_jabatan' => 'Ketua Kelas', 'lingkup' => 'kelas']);
        $jabatanBendahara = Jabatan::create(['nama_jabatan' => 'Bendahara', 'lingkup' => 'kelas']);

        $siswa = [];
        $users = [];

        foreach (['Ahmad Fauzi', 'Siti Nurhaliza', 'Budi Santoso', 'Dewi Lestari'] as $index => $nama) {
            $user = User::create([
                'username' => 'siswa'.($index + 1),
                'password_hash' => 'password',
                'role' => Role::Siswa,
                'is_active' => true,
            ]);

            $row = Siswa::create([
                'id_user' => $user->id_user,
                'nis' => '202600'.($index + 1),
                'nisn' => '00612340'.($index + 1),
                'nama_siswa' => $nama,
                'jenis_kelamin' => $index % 2 === 0 ? 'L' : 'P',
                'status' => 'aktif',
            ]);

            AnggotaKelas::create([
                'id_kelas' => $kelas->id_kelas,
                'id_siswa' => $row->id_siswa,
                'no_absen' => $index + 1,
            ]);

            $siswa[] = $row;
            $users[] = $user;
        }

        PengurusKelas::create([
            'id_kelas' => $kelas->id_kelas,
            'id_siswa' => $siswa[0]->id_siswa,
            'id_jabatan' => $jabatanKetua->id_jabatan,
        ]);

        PengurusKelas::create([
            'id_kelas' => $kelas->id_kelas,
            'id_siswa' => $siswa[1]->id_siswa,
            'id_jabatan' => $jabatanBendahara->id_jabatan,
        ]);

        return [
            'tahun' => $tahun,
            'kelas' => $kelas,
            'kelasLain' => $kelasLain,
            'admin' => $admin,
            'ketua' => $users[0],
            'bendahara' => $users[1],
            'anggota' => $users[2],
            'siswa' => $siswa,
            'users' => $users,
        ];
    }
}
