<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrangTuaSeeder extends Seeder
{
    public function run(): void
    {
        $hash = Hash::make('password');
        $now = now();



        $idUser = DB::table('users')->where('username', 'ortu1')->value('id_user')
            ?? DB::table('users')->insertGetId([
                'username' => 'ortu1',
                'password_hash' => $hash,
                'role' => 'orang_tua',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $idOrangTua = DB::table('orang_tua')->where('id_user', $idUser)->value('id_orang_tua')
            ?? DB::table('orang_tua')->insertGetId([
                'id_user' => $idUser,
                'nama' => 'Ibu Contoh Wali',
                'no_hp' => '081200000001',
                'email' => 'ortu1@example.com',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => null,
            ]);





        $idAnak = DB::table('siswa')->orderBy('id_siswa')->limit(2)->pluck('id_siswa');

        if ($idAnak->isEmpty()) {
            $this->command?->warn(
                'OrangTuaSeeder: belum ada baris di tabel siswa, jadi relasi anak dilewati. '
                .'Jalankan DatabaseSeeder lebih dulu.'
            );

            return;
        }

        foreach ($idAnak->values() as $i => $idSiswa) {
            $sudahAda = DB::table('orang_tua_siswa')
                ->where('id_orang_tua', $idOrangTua)
                ->where('id_siswa', $idSiswa)
                ->exists();

            if (! $sudahAda) {
                DB::table('orang_tua_siswa')->insert([
                    'id_orang_tua' => $idOrangTua,
                    'id_siswa' => $idSiswa,
                    'hubungan' => 'ibu',

                    'is_kontak_utama' => $i === 0,
                ]);
            }
        }










        $this->isiPresensiContoh($idAnak->values()->all(), $now);
    }


    private function isiPresensiContoh(array $idAnak, \Illuminate\Support\Carbon $now): void
    {
        if (DB::table('presensi')->exists()) {
            return;
        }

        $idJadwal = DB::table('jadwal_pelajaran')->orderBy('id_jadwal')->value('id_jadwal');

        if (! $idJadwal) {
            $this->command?->warn(
                'OrangTuaSeeder: belum ada jadwal_pelajaran, jadi presensi contoh dilewati.'
            );

            return;
        }


        $tanggal = [
            $now->copy()->subDays(1)->toDateString(),
            $now->copy()->subDays(2)->toDateString(),
            $now->copy()->subDays(3)->toDateString(),
        ];




        $pola = [
            ['hadir', 'hadir', 'izin'],
            ['hadir', 'alpa', 'sakit'],
        ];

        foreach ($tanggal as $i => $tgl) {
            $idSesi = DB::table('sesi_presensi')
                ->where('id_jadwal', $idJadwal)
                ->where('tanggal', $tgl)
                ->value('id_sesi')
                ?? DB::table('sesi_presensi')->insertGetId([
                    'id_jadwal' => $idJadwal,
                    'tanggal' => $tgl,
                    'dibuka_oleh' => null,
                    'dibuka_pada' => $tgl.' 07:30:00',
                    'ditutup_pada' => $tgl.' 09:00:00',
                    'status' => 'ditutup',
                ]);

            foreach ($idAnak as $urutan => $idSiswa) {
                $status = $pola[$urutan][$i] ?? 'hadir';

                $sudahAda = DB::table('presensi')
                    ->where('id_sesi', $idSesi)
                    ->where('id_siswa', $idSiswa)
                    ->exists();

                if (! $sudahAda) {

                    DB::table('presensi')->insert([
                        'id_sesi' => $idSesi,
                        'id_siswa' => $idSiswa,
                        'status' => $status,
                        'metode' => 'manual',
                        'waktu_presensi' => $tgl.' 07:35:00',
                        'catatan' => null,
                    ]);
                }
            }
        }
    }
}
