<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh fitur 7: pemantauan orang tua/wali.
 *
 * Seeder ini TERPISAH dari DatabaseSeeder karena akun orang tua tidak dibuat di
 * sana. Jalankan setelah siswa ada:
 *
 *     php artisan db:seed --class=OrangTuaSeeder
 *
 * Idempoten: memakai pengecekan keberadaan, jadi aman dijalankan berulang tanpa
 * menggandakan akun, relasi, maupun presensi.
 *
 * Akun contoh: ortu1 / password
 */
class OrangTuaSeeder extends Seeder
{
    public function run(): void
    {
        $hash = Hash::make('password');
        $now = now();

        // ---------- Akun orang tua ----------

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

        // ---------- Hubungkan ke anak ----------

        // Dua anak pertama dipakai agar kasus "satu orang tua punya beberapa anak"
        // benar-benar teruji di portal.
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
                    // Anak pertama ditandai sebagai kontak utama.
                    'is_kontak_utama' => $i === 0,
                ]);
            }
        }

        // ---------- Presensi contoh (opsional) ----------

        // Portal menampilkan persentase kehadiran lewat fn_persentase_kehadiran, yang
        // membaca tabel presensi. Tanpa data presensi, halaman detail hanya menampilkan
        // 0,00% dan "Belum ada data presensi" sehingga sulit diperiksa.
        //
        // Blok ini sengaja hanya jalan bila tabel presensi MASIH KOSONG: kalau fitur
        // presensi sudah punya data sendiri, data itu tidak dicampuri. Hapus blok ini
        // bila data presensi sepenuhnya dikelola fitur lain.
        $this->isiPresensiContoh($idAnak->values()->all(), $now);
    }

    /** @param  array<int, int>  $idAnak */
    private function isiPresensiContoh(array $idAnak, \Illuminate\Support\Carbon $now): void
    {
        if (DB::table('presensi')->exists()) {
            return; // sudah ada data presensi, jangan dicampuri
        }

        $idJadwal = DB::table('jadwal_pelajaran')->orderBy('id_jadwal')->value('id_jadwal');

        if (! $idJadwal) {
            $this->command?->warn(
                'OrangTuaSeeder: belum ada jadwal_pelajaran, jadi presensi contoh dilewati.'
            );

            return;
        }

        // Tiga sesi pada tiga hari terakhir, supaya saringan bulan di portal ada isinya.
        $tanggal = [
            $now->copy()->subDays(1)->toDateString(),
            $now->copy()->subDays(2)->toDateString(),
            $now->copy()->subDays(3)->toDateString(),
        ];

        // Status berbeda per anak agar persentasenya tidak seragam:
        //   anak 1: hadir, hadir, izin   -> 66,67%
        //   anak 2: hadir, alpa, sakit   -> 33,33%
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
                    // Trigger trg_after_presensi_insert otomatis mengisi log_presensi.
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
