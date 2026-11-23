<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected array $d = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(DBDynamicConnection::class);
        $this->d = $this->seedSekolah();
    }

    protected function admin(): User
    {
        return User::findOrFail($this->d['id_admin']);
    }

    protected function sebagaiAdmin(): static
    {
        return $this->actingAs($this->admin());
    }

    protected function buatUser(string $username, string $role, bool $aktif = true): int
    {
        return DB::table('users')->insertGetId([
            'username' => $username, 'password_hash' => bcrypt('password'),
            'role' => $role, 'is_active' => $aktif, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function buatSiswa(string $nama, string $nis, ?int $idKelas = null, ?int $noAbsen = null): int
    {
        $id = DB::table('siswa')->insertGetId([
            'id_user' => $this->buatUser('s'.$nis, 'siswa'),
            'nis' => $nis, 'nisn' => str_pad($nis, 10, '0', STR_PAD_LEFT),
            'nama_siswa' => $nama, 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);

        if ($idKelas) {
            DB::table('anggota_kelas')->insert(['id_kelas' => $idKelas, 'id_siswa' => $id, 'no_absen' => $noAbsen]);
        }

        return $id;
    }

    protected function buatGuru(string $nama, string $nip): int
    {
        return DB::table('guru')->insertGetId([
            'id_user' => $this->buatUser('g'.$nip, 'guru'),
            'nip' => $nip, 'nama_guru' => $nama, 'jenis_kelamin' => 'P',
        ]);
    }


    private function seedSekolah(): array
    {
        $d['id_admin'] = $this->buatUser('admin_uji', 'admin');
        $d['id_ta'] = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-13', 'tanggal_selesai' => '2027-06-26', 'is_aktif' => true,
        ]);
        $d['id_guru'] = $this->buatGuru('Bu Guru', '198001012005012001');
        $d['id_guru2'] = $this->buatGuru('Pak Guru', '198101012005011002');
        $d['id_kelas'] = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $d['id_ta'], 'nama_kelas' => 'X-1', 'tingkat' => 10, 'id_wali_kelas' => $d['id_guru'],
        ]);
        $d['id_kelas2'] = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $d['id_ta'], 'nama_kelas' => 'X-2', 'tingkat' => 10,
        ]);
        $d['id_siswa1'] = $this->buatSiswa("Ani Ma'ruf", '1001', $d['id_kelas'], 1);
        $d['id_siswa2'] = $this->buatSiswa('Budi', '1002', $d['id_kelas'], 2);
        $d['id_siswa3'] = $this->buatSiswa('Citra', '1003');

        $d['id_ortu'] = DB::table('orang_tua')->insertGetId(['id_user' => $this->buatUser('ortu1', 'orang_tua'), 'nama' => 'Ayah Ani']);
        DB::table('orang_tua_siswa')->insert(['id_orang_tua' => $d['id_ortu'], 'id_siswa' => $d['id_siswa1'], 'hubungan' => 'ayah', 'is_kontak_utama' => true]);

        $d['id_mapel'] = DB::table('mata_pelajaran')->insertGetId(['kode_mapel' => 'MTK', 'nama_mapel' => 'Matematika']);
        $d['id_jadwal'] = DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $d['id_kelas'], 'id_mapel' => $d['id_mapel'], 'id_guru' => $d['id_guru'],
            'semester' => 'ganjil', 'hari' => 'senin', 'jam_mulai' => '07:30:00', 'jam_selesai' => '09:00:00',
        ]);
        $d['id_sesi'] = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $d['id_jadwal'], 'tanggal' => today()->toDateString(), 'dibuka_oleh' => $d['id_guru'],
            'dibuka_pada' => now(), 'status' => 'ditutup',
        ]);
        DB::table('presensi')->insert([
            'id_sesi' => $d['id_sesi'], 'id_siswa' => $d['id_siswa1'], 'status' => 'hadir', 'metode' => 'manual', 'waktu_presensi' => now(),
        ]);
        DB::table('pengajuan_izin')->insert([
            'id_siswa' => $d['id_siswa2'], 'jenis' => 'sakit', 'tanggal_mulai' => today()->toDateString(),
            'tanggal_selesai' => today()->toDateString(), 'alasan' => 'Demam', 'status' => 'menunggu', 'diajukan_pada' => now(),
        ]);

        $d['id_bendahara'] = DB::table('jabatan')->insertGetId(['nama_jabatan' => 'Bendahara', 'lingkup' => 'kelas', 'kuota' => 1, 'urutan' => 3]);
        $d['id_ketua_org'] = DB::table('jabatan')->insertGetId(['nama_jabatan' => 'Ketua', 'lingkup' => 'organisasi', 'kuota' => 1, 'urutan' => 1]);
        $d['id_org'] = DB::table('organisasi')->insertGetId(['nama_organisasi' => 'OSIS', 'singkatan' => 'OSIS', 'id_pembina' => $d['id_guru2']]);

        $d['id_transaksi'] = DB::table('transaksi_kas')->insertGetId([
            'id_kelas' => $d['id_kelas'], 'jenis' => 'pemasukan', 'kategori' => 'Iuran', 'nominal' => 50000,
            'tanggal' => today()->toDateString(), 'dicatat_oleh' => DB::table('siswa')->where('id_siswa', $d['id_siswa1'])->value('id_user'),
        ]);

        $d['id_denah'] = DB::table('denah_kelas')->insertGetId([
            'id_kelas' => $d['id_kelas'], 'jumlah_baris' => 2, 'jumlah_kolom' => 2, 'posisi_meja_guru' => 'depan_tengah',
            'berlaku_mulai' => today()->toDateString(), 'dibuat_oleh' => $d['id_admin'], 'is_aktif' => true,
        ]);

        return $d;
    }
}
