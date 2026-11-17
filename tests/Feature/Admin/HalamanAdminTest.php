<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\File;

class HalamanAdminTest extends AdminTestCase
{
    /** Setiap halaman GET di panel admin terbuka (200) dengan data nyata. */
    public function test_semua_halaman_admin_dapat_dibuka(): void
    {
        $d = $this->d;
        $halaman = [
            route('admin.dashboard'),
            route('admin.akun.index'), route('admin.akun.create'), route('admin.akun.edit', $d['id_admin']),
            route('admin.akun.index', ['role' => 'siswa', 'status' => 'aktif', 'q' => 'Ani']),
            route('admin.siswa.index'), route('admin.siswa.index', ['kelas' => $d['id_kelas'], 'q' => '100']),
            route('admin.siswa.create'), route('admin.siswa.edit', $d['id_siswa1']),
            route('admin.guru.index'), route('admin.guru.create'), route('admin.guru.edit', $d['id_guru']),
            route('admin.orang-tua.index'), route('admin.orang-tua.index', ['q' => 'Ani']), route('admin.orang-tua.create'),
            route('admin.orang-tua.show', $d['id_ortu']), route('admin.orang-tua.edit', $d['id_ortu']),
            route('admin.tahun-ajaran.index'), route('admin.tahun-ajaran.edit', $d['id_ta']),
            route('admin.mapel.index'), route('admin.mapel.edit', $d['id_mapel']),
            route('admin.jabatan.index'), route('admin.jabatan.edit', $d['id_bendahara']),
            route('admin.kelas.index'), route('admin.kelas.create'), route('admin.kelas.show', $d['id_kelas']), route('admin.kelas.edit', $d['id_kelas']),
            route('admin.jadwal.index'), route('admin.jadwal.index', ['kelas' => $d['id_kelas'], 'semester' => 'ganjil']),
            route('admin.jadwal.edit', $d['id_jadwal']),
            route('admin.organisasi.index'), route('admin.organisasi.create'), route('admin.organisasi.edit', $d['id_org']),
            route('admin.pengurus.index'), route('admin.pengurus.kelas', $d['id_kelas']), route('admin.pengurus.organisasi', $d['id_org']),
            route('admin.presensi.index'), route('admin.presensi.index', ['kelas' => $d['id_kelas']]),
            route('admin.presensi.sesi', $d['id_sesi']), route('admin.presensi.izin'), route('admin.presensi.izin', ['status' => 'menunggu']),
            route('admin.kas.index'), route('admin.kas.index', ['batal' => 'hanya', 'jenis' => 'pemasukan']),
            route('admin.denah.index'), route('admin.denah.kelas', $d['id_kelas']), route('admin.denah.edit', $d['id_denah']),
        ];

        foreach ($halaman as $url) {
            $this->sebagaiAdmin()->get($url)->assertOk();
        }
    }

    public function test_selain_admin_ditolak(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $guru = User::whereHas('guru')->firstOrFail();
        $this->actingAs($guru)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($guru)->post(route('admin.akun.store'), [])->assertForbidden();
    }

    public function test_id_ngawur_menjadi_404_bukan_500(): void
    {
        $this->sebagaiAdmin()->get('/admin/siswa/99999999999999999999/edit')->assertNotFound();
        $this->sebagaiAdmin()->get('/admin/kelas/abc')->assertNotFound();
    }

    public function test_nama_berapostrof_tidak_merusak_konfirmasi(): void
    {
        // Konfirmasi memakai data-konfirmasi (di-escape Blade), bukan onclick="confirm('...')".
        $this->sebagaiAdmin()->get(route('admin.kelas.show', $this->d['id_kelas']))
            ->assertOk()
            ->assertSee('data-konfirmasi="Keluarkan Ani Ma&#039;ruf', false)
            ->assertDontSee('onclick="return confirm', false);
    }

    /** Batas keras no. 17: tidak ada kode admin yang menyentuh tabel suara (anonimitas pemilihan). */
    public function test_kode_admin_tidak_menyentuh_tabel_suara(): void
    {
        foreach (File::allFiles(app_path('Http/Controllers/Admin')) as $file) {
            $this->assertDoesNotMatchRegularExpression("/['\"`]suara['\"`]/", $file->getContents(), $file->getFilename());
        }
    }
}
