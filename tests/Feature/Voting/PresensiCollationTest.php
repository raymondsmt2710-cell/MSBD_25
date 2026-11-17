<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi bug collation: parameter Stored Function mewarisi collation default
 * database (utf8mb4_0900_ai_ci) sedangkan presensi.status adalah ENUM dengan
 * collation utf8mb4_unicode_ci, sehingga fn_jumlah_presensi() selalu error 1267.
 *
 * Bug ini memblokir login siswa karena halaman riwayat presensi menjadi tujuan
 * redirect setelah login.
 */
class PresensiCollationTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_fn_jumlah_presensi_bisa_dipanggil(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $hasil = DB::connection('mysql')->selectOne(
            'SELECT fn_jumlah_presensi(?, ?) AS v',
            [$d['id_siswa'][0], 'hadir']
        );

        $this->assertSame(0, (int) $hasil->v);
    }

    public function test_fn_jumlah_presensi_menghitung_status_dengan_benar(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        // Siapkan satu sesi presensi dan satu baris presensi 'hadir'.
        $idJadwal = DB::connection('mysql')->table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $d['id_kelas'],
            'id_mapel' => DB::connection('mysql')->table('mata_pelajaran')->insertGetId([
                'kode_mapel' => 'UJI', 'nama_mapel' => 'Uji',
            ]),
            'id_guru' => DB::connection('mysql')->table('guru')->value('id_guru'),
            'semester' => 'ganjil',
            'hari' => 'senin',
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
        ]);

        $idSesi = DB::connection('mysql')->table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => now()->toDateString(),
            'dibuka_oleh' => DB::connection('mysql')->table('guru')->value('id_guru'),
            'dibuka_pada' => now(),
            'status' => 'dibuka',
        ]);

        DB::connection('mysql')->table('presensi')->insert([
            'id_sesi' => $idSesi,
            'id_siswa' => $d['id_siswa'][0],
            'status' => 'hadir',
            'metode' => 'manual',
        ]);

        $hadir = DB::connection('mysql')->selectOne('SELECT fn_jumlah_presensi(?, ?) AS v', [$d['id_siswa'][0], 'hadir']);
        $alpa = DB::connection('mysql')->selectOne('SELECT fn_jumlah_presensi(?, ?) AS v', [$d['id_siswa'][0], 'alpa']);

        $this->assertSame(1, (int) $hadir->v);
        $this->assertSame(0, (int) $alpa->v);
    }

    public function test_halaman_riwayat_presensi_siswa_bisa_dibuka(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.presensi.riwayat'))
            ->assertOk();
    }
}
