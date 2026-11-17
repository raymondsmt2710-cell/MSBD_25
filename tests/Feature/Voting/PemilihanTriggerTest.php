<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PemilihanTriggerTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    private function masukkanSuara(array $d, int $indeksKandidat = 0): string
    {
        $id = Str::uuid()->toString();
        DB::table('suara')->insert([
            'id_suara' => $id,
            'id_pemilihan' => $d['id_pemilihan'],
            'id_kandidat' => $d['id_kandidat'][$indeksKandidat],
        ]);

        return $id;
    }

    public function test_suara_tidak_bisa_diubah(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        $this->masukkanSuara($d);

        $this->expectException(\Throwable::class);

        DB::table('suara')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->update(['id_kandidat' => $d['id_kandidat'][1]]);
    }

    public function test_suara_tidak_bisa_dihapus(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        $this->masukkanSuara($d);

        $this->expectException(\Throwable::class);

        DB::table('suara')->where('id_pemilihan', $d['id_pemilihan'])->delete();
    }

    public function test_kandidat_bersuara_tidak_bisa_dihapus(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        $this->masukkanSuara($d);

        $this->expectException(\Throwable::class);

        DB::table('kandidat')->where('id_kandidat', $d['id_kandidat'][0])->delete();
    }

    public function test_kandidat_tanpa_suara_tetap_bisa_dihapus(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        DB::table('kandidat')->where('id_kandidat', $d['id_kandidat'][1])->delete();

        $this->assertSame(1, DB::table('kandidat')->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_pemilih_yang_sudah_memilih_tidak_bisa_dihapus(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->update(['sudah_memilih' => 1]);

        $this->expectException(\Throwable::class);

        DB::table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->delete();
    }

    public function test_data_pemilihan_terkunci_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->update(['waktu_selesai' => now()->addDays(1)]);
    }

    public function test_judul_tidak_bisa_diubah_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->update(['judul' => 'Judul Baru']);
    }

    public function test_status_tetap_bisa_diubah_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->update(['status' => 'selesai']);

        $this->assertSame('selesai', DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
    }

    public function test_perubahan_status_tercatat_di_log(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);

        $log = DB::table('log_pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('aksi', 'STATUS_BERUBAH')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('berlangsung', $log->status_lama);
        $this->assertSame('selesai', $log->status_baru);
    }

    public function test_suara_ditolak_untuk_pemilihan_yang_belum_berlangsung(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->expectException(\Throwable::class);

        DB::table('suara')->insert([
            'id_suara' => Str::uuid()->toString(),
            'id_pemilihan' => $d['id_pemilihan'],
            'id_kandidat' => $d['id_kandidat'][0],
        ]);
    }

    public function test_suara_ditolak_untuk_kandidat_milik_pemilihan_lain(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        $d2 = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::table('suara')->insert([
            'id_suara' => Str::uuid()->toString(),
            'id_pemilihan' => $d['id_pemilihan'],
            'id_kandidat' => $d2['id_kandidat'][0],
        ]);
    }

    public function test_pemilih_mencatat_waktu_tanpa_membocorkan_kandidat(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        DB::table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->update(['sudah_memilih' => 1, 'waktu_memilih' => now()]);

        $log = DB::table('log_pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('aksi', 'PEMILIH_MEMILIH')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString('kandidat', strtolower($log->keterangan ?? ''));
    }
}
