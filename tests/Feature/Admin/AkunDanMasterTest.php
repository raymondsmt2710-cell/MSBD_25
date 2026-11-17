<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Fitur 1–7. */
class AkunDanMasterTest extends AdminTestCase
{
    public function test_buat_akun_siswa_tanpa_password_menampilkan_sandi_sekali_dan_mengarah_ke_profil(): void
    {
        $res = $this->sebagaiAdmin()->post(route('admin.akun.store'), ['username' => 'siswa.baru', 'role' => 'siswa', 'is_active' => 1]);

        $user = User::where('username', 'siswa.baru')->firstOrFail();
        $res->assertRedirect(route('admin.siswa.create', ['id_user' => $user->id_user]))
            ->assertSessionHas('sandi_baru');

        $this->assertTrue(Hash::check(session('sandi_baru'), $user->password_hash));
        $this->assertSame('siswa', $user->role);
    }

    public function test_admin_tidak_bisa_menonaktifkan_diri_sendiri_atau_admin_terakhir(): void
    {
        $this->sebagaiAdmin()->post(route('admin.akun.status', $this->d['id_admin']))->assertSessionHas('error');
        $this->assertTrue($this->admin()->fresh()->is_active);

        $this->sebagaiAdmin()->put(route('admin.akun.update', $this->d['id_admin']), ['username' => 'admin_uji', 'role' => 'guru'])
            ->assertSessionHas('error');
        $this->assertSame('admin', $this->admin()->fresh()->role);
    }

    public function test_peran_akun_berprofil_terkunci(): void
    {
        $idUser = DB::table('siswa')->where('id_siswa', $this->d['id_siswa1'])->value('id_user');

        $this->sebagaiAdmin()->put(route('admin.akun.update', $idUser), ['username' => 's1001', 'role' => 'guru'])
            ->assertSessionHas('error');
        $this->assertSame('siswa', User::find($idUser)->role);
    }

    public function test_menonaktifkan_akun_memutus_session_yang_terbuka(): void
    {
        $idUser = $this->buatUser('ortu.aktif', 'orang_tua');
        DB::table('orang_tua')->insert(['id_user' => $idUser, 'nama' => 'Ibu Uji']);

        $this->post('/login', ['username' => 'ortu.aktif', 'password' => 'password'])->assertRedirect();
        $this->get('/ortu')->assertOk();

        $this->sebagaiAdmin()->post(route('admin.akun.status', $idUser))->assertSessionHas('success');
        $this->assertFalse(User::find($idUser)->is_active);

        // Kembali sebagai session orang tua tadi: middleware PastikanAkunAktif harus memutusnya.
        Auth::forgetGuards();
        $this->actingAs(User::find($idUser))->get('/ortu')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_reset_password_mengganti_hash(): void
    {
        $idUser = DB::table('guru')->where('id_guru', $this->d['id_guru'])->value('id_user');
        $lama = User::find($idUser)->password_hash;

        $this->sebagaiAdmin()->post(route('admin.akun.reset', $idUser))->assertSessionHas('sandi_baru');

        $baru = User::find($idUser)->password_hash;
        $this->assertNotSame($lama, $baru);
        $this->assertTrue(Hash::check(session('sandi_baru'), $baru));
    }

    public function test_profil_siswa_hanya_untuk_akun_bertipe_siswa_yang_belum_berprofil(): void
    {
        $idGuruUser = $this->buatUser('bukan.siswa', 'guru');
        $data = ['nis' => '2001', 'nisn' => '0000002001', 'nama_siswa' => 'Dodi', 'jenis_kelamin' => 'L', 'status' => 'aktif'];

        $this->sebagaiAdmin()->post(route('admin.siswa.store'), $data + ['id_user' => $idGuruUser])->assertSessionHasErrors('id_user');

        $idSiswaUser = $this->buatUser('calon.siswa', 'siswa');
        $this->sebagaiAdmin()->post(route('admin.siswa.store'), $data + ['id_user' => $idSiswaUser])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('siswa', ['id_user' => $idSiswaUser, 'nis' => '2001']);

        // NISN wajib 10 digit dan unik.
        $this->sebagaiAdmin()->post(route('admin.siswa.store'), ['nisn' => '123'] + $data + ['id_user' => $this->buatUser('x', 'siswa')])
            ->assertSessionHasErrors(['nisn']);
    }

    public function test_status_siswa_keluar_menonaktifkan_akun_dan_aktif_memulihkannya(): void
    {
        $idUser = DB::table('siswa')->where('id_siswa', $this->d['id_siswa2'])->value('id_user');

        $this->sebagaiAdmin()->post(route('admin.siswa.status', $this->d['id_siswa2']), ['status' => 'pindah', 'nonaktifkan_akun' => 1]);
        $this->assertFalse(User::find($idUser)->is_active);

        $this->sebagaiAdmin()->post(route('admin.siswa.status', $this->d['id_siswa2']), ['status' => 'aktif']);
        $this->assertTrue(User::find($idUser)->is_active);
    }

    public function test_siswa_tidak_boleh_punya_dua_ayah_dan_kontak_utama_tunggal(): void
    {
        $idOrtu2 = DB::table('orang_tua')->insertGetId(['id_user' => $this->buatUser('ortu2', 'orang_tua'), 'nama' => 'Orang Lain']);

        $this->sebagaiAdmin()->post(route('admin.orang-tua.anak.simpan', $idOrtu2), ['id_siswa' => $this->d['id_siswa1'], 'hubungan' => 'ayah'])
            ->assertSessionHas('error');

        $this->sebagaiAdmin()->post(route('admin.orang-tua.anak.simpan', $idOrtu2), [
            'id_siswa' => $this->d['id_siswa1'], 'hubungan' => 'ibu', 'is_kontak_utama' => 1,
        ])->assertSessionHas('success');

        $utama = DB::table('orang_tua_siswa')->where('id_siswa', $this->d['id_siswa1'])->where('is_kontak_utama', true)->pluck('id_orang_tua');
        $this->assertSame([$idOrtu2], $utama->map(fn ($v) => (int) $v)->all());
    }

    public function test_hanya_satu_tahun_ajaran_aktif_dan_periode_tidak_tumpang_tindih(): void
    {
        $this->sebagaiAdmin()->post(route('admin.tahun-ajaran.store'), [
            'nama' => '2027/2028', 'tanggal_mulai' => '2027-01-01', 'tanggal_selesai' => '2027-12-31',
        ])->assertSessionHasErrors('tanggal_mulai');

        $this->sebagaiAdmin()->post(route('admin.tahun-ajaran.store'), [
            'nama' => '2027/2029', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-24',
        ])->assertSessionHasErrors('nama');

        $this->sebagaiAdmin()->post(route('admin.tahun-ajaran.store'), [
            'nama' => '2027/2028', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-24',
        ])->assertSessionHasNoErrors();

        $baru = DB::table('tahun_ajaran')->where('nama', '2027/2028')->value('id_tahun_ajaran');
        $this->sebagaiAdmin()->post(route('admin.tahun-ajaran.aktifkan', $baru));

        $this->assertSame(1, DB::table('tahun_ajaran')->where('is_aktif', true)->count());
        $this->assertTrue((bool) DB::table('tahun_ajaran')->where('id_tahun_ajaran', $baru)->value('is_aktif'));
    }

    public function test_mapel_dan_jabatan_yang_dipakai_tidak_bisa_dihapus(): void
    {
        $this->sebagaiAdmin()->delete(route('admin.mapel.destroy', $this->d['id_mapel']))->assertSessionHas('error');
        $this->assertDatabaseHas('mata_pelajaran', ['id_mapel' => $this->d['id_mapel']]);

        DB::table('pengurus_kelas')->insert(['id_kelas' => $this->d['id_kelas'], 'id_siswa' => $this->d['id_siswa1'], 'id_jabatan' => $this->d['id_bendahara']]);
        $this->sebagaiAdmin()->delete(route('admin.jabatan.destroy', $this->d['id_bendahara']))->assertSessionHas('error');
        $this->assertDatabaseHas('jabatan', ['id_jabatan' => $this->d['id_bendahara']]);
    }
}
