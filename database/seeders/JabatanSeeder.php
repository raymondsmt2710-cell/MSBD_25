<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [

            ['nama_jabatan' => 'Ketua Kelas', 'lingkup' => 'kelas', 'kuota' => 1, 'urutan' => 1],
            ['nama_jabatan' => 'Wakil Ketua Kelas', 'lingkup' => 'kelas', 'kuota' => 1, 'urutan' => 2],
            ['nama_jabatan' => 'Sekretaris', 'lingkup' => 'kelas', 'kuota' => 1, 'urutan' => 3],
            ['nama_jabatan' => 'Bendahara', 'lingkup' => 'kelas', 'kuota' => 1, 'urutan' => 4],
            ['nama_jabatan' => 'Seksi Kebersihan', 'lingkup' => 'kelas', 'kuota' => 1, 'urutan' => 5],
            ['nama_jabatan' => 'Seksi Keamanan', 'lingkup' => 'kelas', 'kuota' => 1, 'urutan' => 6],


            ['nama_jabatan' => 'Ketua', 'lingkup' => 'organisasi', 'kuota' => 1, 'urutan' => 1],
            ['nama_jabatan' => 'Wakil Ketua', 'lingkup' => 'organisasi', 'kuota' => 1, 'urutan' => 2],
            ['nama_jabatan' => 'Sekretaris', 'lingkup' => 'organisasi', 'kuota' => 1, 'urutan' => 3],
            ['nama_jabatan' => 'Bendahara', 'lingkup' => 'organisasi', 'kuota' => 1, 'urutan' => 4],
            ['nama_jabatan' => 'Koordinator Bidang', 'lingkup' => 'organisasi', 'kuota' => 5, 'urutan' => 5],
        ];

        foreach ($daftar as $j) {
            $ada = DB::table('jabatan')
                ->where('nama_jabatan', $j['nama_jabatan'])
                ->where('lingkup', $j['lingkup'])
                ->exists();

            if (! $ada) {
                DB::table('jabatan')->insert($j);
            }
        }
    }
}
