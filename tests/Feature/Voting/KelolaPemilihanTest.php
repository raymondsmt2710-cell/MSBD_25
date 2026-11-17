<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KelolaPemilihanTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_admin_membuat_pemilihan_baru_sebagai_draf(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.simpan'), [
                'judul' => 'Pemilihan Ketua OSIS 2027',
                'deskripsi' => 'Uji',
                'id_organisasi' => $d['id_organisasi'],
                'id_tahun_ajaran' => $d['id_tahun_ajaran'],
                'waktu_mulai' => now()->addDay()->format('Y-m-d\TH:i'),
                'waktu_selesai' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ])->assertRedirect();

        $this->assertDatabaseHas('pemilihan', [
            'judul' => 'Pemilihan Ketua OSIS 2027',
            'status' => 'draf',
        ]);
    }

    public function test_validasi_waktu_selesai_harus_setelah_mulai(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.simpan'), [
                'judul' => 'X',
                'id_organisasi' => $d['id_organisasi'],
                'id_tahun_ajaran' => $d['id_tahun_ajaran'],
                'waktu_mulai' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'waktu_selesai' => now()->addDay()->format('Y-m-d\TH:i'),
            ])->assertSessionHasErrors('waktu_selesai');
    }

    public function test_form_daftar_kelola_menampilkan_pemilihan(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.index'))
            ->assertOk()
            ->assertSee('Pemilihan Ketua OSIS Uji');
    }

    public function test_ubah_pemilihan_saat_draf_berhasil(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.ubah.simpan', $d['id_pemilihan']), [
                'judul' => 'Judul Diperbarui',
                'id_organisasi' => $d['id_organisasi'],
                'id_tahun_ajaran' => $d['id_tahun_ajaran'],
                'waktu_mulai' => now()->addHours(2)->format('Y-m-d\TH:i'),
                'waktu_selesai' => now()->addDay()->format('Y-m-d\TH:i'),
            ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Judul Diperbarui', DB::connection('mysql')->table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('judul'));
    }

    public function test_ubah_pemilihan_saat_berlangsung_ditolak(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.ubah.simpan', $d['id_pemilihan']), [
                'judul' => 'Judul Diubah Diam-diam',
                'id_organisasi' => $d['id_organisasi'],
                'id_tahun_ajaran' => $d['id_tahun_ajaran'],
                'waktu_mulai' => now()->format('Y-m-d\TH:i'),
                'waktu_selesai' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ])->assertSessionHas('error');

        $this->assertSame('Pemilihan Ketua OSIS Uji', DB::connection('mysql')->table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('judul'));
    }
}
