<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;

class AkademikDanPemantauanTest extends AdminTestCase
{
    public function test_siswa_hanya_satu_kelas_per_tahun_ajaran(): void
    {
        $this->sebagaiAdmin()->post(route('admin.kelas.anggota.simpan', $this->d['id_kelas2']), ['id_siswa' => [$this->d['id_siswa1']]])
            ->assertSessionHasErrors('id_siswa');

        $this->sebagaiAdmin()->post(route('admin.kelas.anggota.simpan', $this->d['id_kelas2']), ['id_siswa' => [$this->d['id_siswa3']]])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('anggota_kelas', ['id_kelas' => $this->d['id_kelas2'], 'id_siswa' => $this->d['id_siswa3'], 'no_absen' => 1]);
    }

    public function test_guru_hanya_wali_satu_kelas_per_tahun_ajaran(): void
    {
        $this->sebagaiAdmin()->put(route('admin.kelas.update', $this->d['id_kelas2']), [
            'id_tahun_ajaran' => $this->d['id_ta'], 'nama_kelas' => 'X-2', 'tingkat' => 10, 'id_wali_kelas' => $this->d['id_guru'],
        ])->assertSessionHasErrors('id_wali_kelas');
    }

    public function test_kelas_berisi_tidak_bisa_dihapus(): void
    {
        $this->sebagaiAdmin()->delete(route('admin.kelas.destroy', $this->d['id_kelas']))->assertSessionHas('error');
        $this->assertDatabaseHas('kelas', ['id_kelas' => $this->d['id_kelas']]);
    }

    public function test_nomor_absen_ganda_ditolak_dan_urutkan_mengikuti_abjad(): void
    {
        $anggota = DB::table('anggota_kelas')->where('id_kelas', $this->d['id_kelas'])->pluck('id_anggota');

        $this->sebagaiAdmin()->put(route('admin.kelas.anggota.absen', $this->d['id_kelas']), [
            'no_absen' => [$anggota[0] => 5, $anggota[1] => 5],
        ])->assertSessionHas('error');

        $this->sebagaiAdmin()->post(route('admin.kelas.anggota.urutkan', $this->d['id_kelas']));
        $this->assertSame(1, (int) DB::table('anggota_kelas')->where('id_siswa', $this->d['id_siswa1'])->value('no_absen'));
        $this->assertSame(2, (int) DB::table('anggota_kelas')->where('id_siswa', $this->d['id_siswa2'])->value('no_absen'));
    }

    public function test_jadwal_bentrok_kelas_dan_guru_ditolak(): void
    {
        $dasar = ['id_mapel' => $this->d['id_mapel'], 'semester' => 'ganjil', 'hari' => 'senin'];


        $this->sebagaiAdmin()->post(route('admin.jadwal.store'), $dasar + [
            'id_kelas' => $this->d['id_kelas'], 'id_guru' => $this->d['id_guru2'], 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
        ])->assertSessionHasErrors('jam_mulai');


        $this->sebagaiAdmin()->post(route('admin.jadwal.store'), $dasar + [
            'id_kelas' => $this->d['id_kelas2'], 'id_guru' => $this->d['id_guru'], 'jam_mulai' => '08:30', 'jam_selesai' => '10:00',
        ])->assertSessionHasErrors('id_guru');


        $this->sebagaiAdmin()->post(route('admin.jadwal.store'), $dasar + [
            'id_kelas' => $this->d['id_kelas'], 'id_guru' => $this->d['id_guru'], 'jam_mulai' => '09:00', 'jam_selesai' => '10:30',
        ])->assertSessionHasNoErrors();
    }

    public function test_jadwal_yang_sudah_punya_sesi_tidak_bisa_dihapus(): void
    {
        $this->sebagaiAdmin()->delete(route('admin.jadwal.destroy', $this->d['id_jadwal']))->assertSessionHas('error');
        $this->assertDatabaseHas('presensi', ['id_sesi' => $this->d['id_sesi']]);
    }

    public function test_pengurus_kelas_wajib_anggota_dan_kuota_dijaga(): void
    {
        $url = route('admin.pengurus.kelas.simpan', $this->d['id_kelas']);

        $this->sebagaiAdmin()->post($url, ['id_jabatan' => $this->d['id_bendahara'], 'id_siswa' => $this->d['id_siswa3']])
            ->assertSessionHasErrors('id_siswa');

        $this->sebagaiAdmin()->post($url, ['id_jabatan' => $this->d['id_bendahara'], 'id_siswa' => $this->d['id_siswa1']])
            ->assertSessionHasNoErrors();


        $this->sebagaiAdmin()->post($url, ['id_jabatan' => $this->d['id_bendahara'], 'id_siswa' => $this->d['id_siswa2']])
            ->assertSessionHasErrors('id_jabatan');


        $this->sebagaiAdmin()->post($url, ['id_jabatan' => $this->d['id_ketua_org'], 'id_siswa' => $this->d['id_siswa2']])
            ->assertSessionHasErrors('id_jabatan');
    }

    public function test_pengurus_organisasi_per_tahun_ajaran(): void
    {
        $this->sebagaiAdmin()->post(route('admin.pengurus.organisasi.simpan', $this->d['id_org']), [
            'id_jabatan' => $this->d['id_ketua_org'], 'id_siswa' => $this->d['id_siswa3'], 'bidang' => 'Inti',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengurus_organisasi', [
            'id_organisasi' => $this->d['id_org'], 'id_tahun_ajaran' => $this->d['id_ta'], 'id_siswa' => $this->d['id_siswa3'],
        ]);
    }

    public function test_organisasi_dengan_riwayat_pemilihan_tidak_bisa_dihapus(): void
    {
        DB::table('pemilihan')->insert([
            'id_organisasi' => $this->d['id_org'], 'id_tahun_ajaran' => $this->d['id_ta'], 'judul' => 'Pemilu OSIS',
            'waktu_mulai' => now()->addDay(), 'waktu_selesai' => now()->addDays(2), 'status' => 'draf', 'dibuat_oleh' => $this->d['id_admin'],
        ]);

        $this->sebagaiAdmin()->delete(route('admin.organisasi.destroy', $this->d['id_org']))->assertSessionHas('error');
        $this->assertDatabaseHas('organisasi', ['id_organisasi' => $this->d['id_org']]);
    }

    public function test_pembatalan_kas_tercatat_dan_tidak_bisa_diulang(): void
    {
        $url = route('admin.kas.batal', $this->d['id_transaksi']);

        $this->sebagaiAdmin()->post($url, [])->assertSessionHasErrors('alasan_batal');

        $this->sebagaiAdmin()->post($url, ['alasan_batal' => 'Salah input nominal'])->assertSessionHas('success');
        $this->assertDatabaseHas('transaksi_kas', [
            'id_transaksi' => $this->d['id_transaksi'], 'is_batal' => true,
            'dibatalkan_oleh' => $this->d['id_admin'], 'alasan_batal' => 'Salah input nominal',
        ]);

        $this->sebagaiAdmin()->post($url, ['alasan_batal' => 'Kedua kali'])->assertSessionHas('error');
        $this->assertSame('Salah input nominal', DB::table('transaksi_kas')->where('id_transaksi', $this->d['id_transaksi'])->value('alasan_batal'));


        $this->sebagaiAdmin()->get(route('admin.kas.index'))->assertOk()->assertSee('Rp 0', false);
    }

    public function test_denah_baru_terisi_urut_absen_dan_menjadi_satu_satunya_yang_aktif(): void
    {
        $this->sebagaiAdmin()->post(route('admin.denah.store', $this->d['id_kelas']), [
            'jumlah_baris' => 3, 'jumlah_kolom' => 2, 'posisi_meja_guru' => 'depan_kiri',
            'berlaku_mulai' => today()->toDateString(), 'isi' => 'absen', 'aktifkan' => 1,
        ])->assertRedirect();

        $baru = DB::table('denah_kelas')->where('id_kelas', $this->d['id_kelas'])->orderByDesc('id_denah')->first();
        $this->assertTrue((bool) $baru->is_aktif);
        $this->assertSame(1, DB::table('denah_kelas')->where('id_kelas', $this->d['id_kelas'])->where('is_aktif', true)->count());
        $this->assertDatabaseHas('posisi_duduk', ['id_denah' => $baru->id_denah, 'id_siswa' => $this->d['id_siswa1'], 'baris' => 1, 'kolom' => 1]);
        $this->assertDatabaseHas('posisi_duduk', ['id_denah' => $baru->id_denah, 'id_siswa' => $this->d['id_siswa2'], 'baris' => 1, 'kolom' => 2]);
    }

    public function test_posisi_duduk_menolak_siswa_ganda_dan_bukan_anggota(): void
    {
        $url = route('admin.denah.posisi', $this->d['id_denah']);

        $this->sebagaiAdmin()->put($url, ['kursi' => [1 => [1 => $this->d['id_siswa1'], 2 => $this->d['id_siswa1']]]])
            ->assertSessionHasErrors('kursi');

        $this->sebagaiAdmin()->put($url, ['kursi' => [1 => [1 => $this->d['id_siswa3']]]])
            ->assertSessionHasErrors('kursi');

        $this->sebagaiAdmin()->put($url, ['kursi' => [3 => [1 => $this->d['id_siswa1']]]])
            ->assertSessionHasErrors('kursi');

        $this->sebagaiAdmin()->put($url, ['kursi' => [2 => [2 => $this->d['id_siswa1'], 1 => $this->d['id_siswa2']]]])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('posisi_duduk')->where('id_denah', $this->d['id_denah'])->count());
    }

    public function test_mengeluarkan_siswa_melepas_jabatan_dan_kursinya(): void
    {
        DB::table('pengurus_kelas')->insert(['id_kelas' => $this->d['id_kelas'], 'id_siswa' => $this->d['id_siswa2'], 'id_jabatan' => $this->d['id_bendahara']]);
        DB::table('posisi_duduk')->insert(['id_denah' => $this->d['id_denah'], 'id_siswa' => $this->d['id_siswa2'], 'baris' => 1, 'kolom' => 1]);
        $idAnggota = DB::table('anggota_kelas')->where('id_siswa', $this->d['id_siswa2'])->value('id_anggota');

        $this->sebagaiAdmin()->delete(route('admin.kelas.anggota.hapus', [$this->d['id_kelas'], $idAnggota]))->assertSessionHas('success');

        $this->assertDatabaseMissing('pengurus_kelas', ['id_siswa' => $this->d['id_siswa2']]);
        $this->assertDatabaseMissing('posisi_duduk', ['id_siswa' => $this->d['id_siswa2']]);
    }
}
