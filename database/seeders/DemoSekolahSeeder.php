<?php

namespace Database\Seeders;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Enums\Role;
use App\Enums\StatusIuran;
use App\Models\AnggotaKelas;
use App\Models\DenahKelas;
use App\Models\Guru;
use App\Models\IuranKas;
use App\Models\Jabatan;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\PengurusKelas;
use App\Models\PosisiDuduk;
use App\Models\RuanganSekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSekolahSeeder extends Seeder
{
    public function run(): void
    {
        $hash = Hash::make('password');

        DB::transaction(function () use ($hash) {
            $tahun = TahunAjaran::firstOrCreate(
                ['nama' => '2026/2027'],
                [
                    'tanggal_mulai' => '2026-07-01',
                    'tanggal_selesai' => '2027-06-30',
                    'is_aktif' => true,
                ],
            );

            TahunAjaran::where('id_tahun_ajaran', '!=', $tahun->id_tahun_ajaran)
                ->update(['is_aktif' => false]);
            $tahun->update(['is_aktif' => true]);

            $guruUser = User::firstOrCreate(
                ['username' => 'guru1'],
                ['password_hash' => $hash, 'role' => 'guru', 'is_active' => true],
            );

            if (! Hash::isHashed((string) $guruUser->password_hash)) {
                $guruUser->update(['password_hash' => $hash]);
            }

            $guru = Guru::firstOrCreate(
                ['id_user' => $guruUser->id_user],
                [
                    'nip' => '198001012005011001',
                    'nama_guru' => 'Budi Santoso, S.Pd.',
                    'jenis_kelamin' => 'L',
                    'no_hp' => '081234567890',
                    'email' => 'guru1@sekolah.test',
                ],
            );

            $kelas = Kelas::firstOrCreate(
                ['id_tahun_ajaran' => $tahun->id_tahun_ajaran, 'nama_kelas' => 'XI IPA 1'],
                [
                    'tingkat' => 11,
                    'jurusan' => 'IPA',
                    'id_wali_kelas' => $guru->id_guru,
                    'ruangan' => 'R-101',
                ],
            );

            $jabatanKetua = Jabatan::firstOrCreate(
                ['nama_jabatan' => 'Ketua Kelas', 'lingkup' => 'kelas'],
                ['kuota' => 1, 'urutan' => 1],
            );
            $jabatanBendahara = Jabatan::firstOrCreate(
                ['nama_jabatan' => 'Bendahara', 'lingkup' => 'kelas'],
                ['kuota' => 1, 'urutan' => 2],
            );
            $jabatanSekretaris = Jabatan::firstOrCreate(
                ['nama_jabatan' => 'Sekretaris', 'lingkup' => 'kelas'],
                ['kuota' => 1, 'urutan' => 3],
            );

            $namaSiswa = [
                'Ahmad Fauzi', 'Siti Nurhaliza', 'Budi Santoso', 'Dewi Lestari',
                'Rizky Pratama', 'Ayu Wulandari', 'Fajar Nugroho', 'Nadia Putri',
            ];

            $daftarSiswa = [];

            foreach ($namaSiswa as $index => $nama) {
                $user = User::firstOrCreate(
                    ['username' => 'siswa'.($index + 1)],
                    ['password_hash' => $hash, 'role' => 'siswa', 'is_active' => true],
                );

                if (! Hash::isHashed((string) $user->password_hash)) {
                    $user->update(['password_hash' => $hash]);
                }

                $siswa = Siswa::firstOrCreate(
                    ['id_user' => $user->id_user],
                    [
                        'nis' => '2026'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'nisn' => '0061234'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'nama_siswa' => $nama,
                        'jenis_kelamin' => $index % 2 === 0 ? 'L' : 'P',
                        'tempat_lahir' => 'Bandung',
                        'tanggal_lahir' => '2009-0'.(($index % 9) + 1).'-15',
                        'alamat' => 'Jl. Pendidikan No. '.($index + 1),
                        'no_hp' => '0812000000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'status' => 'aktif',
                    ],
                );

                AnggotaKelas::firstOrCreate(
                    ['id_kelas' => $kelas->id_kelas, 'id_siswa' => $siswa->id_siswa],
                    ['no_absen' => $index + 1],
                );

                $daftarSiswa[] = $siswa;
            }

            PengurusKelas::firstOrCreate([
                'id_kelas' => $kelas->id_kelas,
                'id_siswa' => $daftarSiswa[0]->id_siswa,
                'id_jabatan' => $jabatanKetua->id_jabatan,
            ]);

            PengurusKelas::firstOrCreate([
                'id_kelas' => $kelas->id_kelas,
                'id_siswa' => $daftarSiswa[1]->id_siswa,
                'id_jabatan' => $jabatanBendahara->id_jabatan,
            ]);

            PengurusKelas::firstOrCreate([
                'id_kelas' => $kelas->id_kelas,
                'id_siswa' => $daftarSiswa[2]->id_siswa,
                'id_jabatan' => $jabatanSekretaris->id_jabatan,
            ]);

            $userBendahara = $daftarSiswa[1]->user;

            // ---------- Akun demo peran "Siswa Bendahara Kas" ----------
            // Peran ini memberi hak kelola kas kelasnya sendiri tanpa menjadi
            // admin. Profilnya tetap baris tabel siswa, jadi presensi/perangkat/
            // pemilihan tetap berlaku. Dibuat terpisah dari 8 siswa di atas agar
            // tidak mengacak daftar nama dan nomor absen yang sudah dipakai
            // matriks iuran.
            $userBendaharaKas = User::firstOrCreate(
                ['username' => 'bendahara'],
                ['password_hash' => $hash, 'role' => Role::BendaharaKas, 'is_active' => true],
            );

            if (! Hash::isHashed((string) $userBendaharaKas->password_hash)) {
                $userBendaharaKas->update(['password_hash' => $hash]);
            }

            // Pastikan perannya benar walau akunnya sudah ada dari seed lama.
            if ($userBendaharaKas->role !== Role::BendaharaKas) {
                $userBendaharaKas->update(['role' => Role::BendaharaKas]);
            }

            $siswaBendaharaKas = Siswa::firstOrCreate(
                ['id_user' => $userBendaharaKas->id_user],
                [
                    'nis' => '2026099',
                    'nisn' => '0061234099',
                    'nama_siswa' => 'Bendahara Kas (Demo)',
                    'jenis_kelamin' => 'L',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '2009-01-15',
                    'alamat' => 'Jl. Pendidikan No. 99',
                    'no_hp' => '08120000099',
                    'status' => 'aktif',
                ],
            );

            $noAbsenBendahara = (int) AnggotaKelas::where('id_kelas', $kelas->id_kelas)
                ->max('no_absen');

            AnggotaKelas::firstOrCreate(
                ['id_kelas' => $kelas->id_kelas, 'id_siswa' => $siswaBendaharaKas->id_siswa],
                ['no_absen' => $noAbsenBendahara + 1],
            );

            $orangTuaUser = User::firstOrCreate(
                ['username' => 'ortu1'],
                ['password_hash' => $hash, 'role' => 'orang_tua', 'is_active' => true],
            );

            if (! Hash::isHashed((string) $orangTuaUser->password_hash)) {
                $orangTuaUser->update(['password_hash' => $hash]);
            }

            $orangTua = OrangTua::firstOrCreate(
                ['id_user' => $orangTuaUser->id_user],
                [
                    'nama' => 'Bapak Ahmad',
                    'no_hp' => '081298765432',
                    'email' => 'ortu1@sekolah.test',
                    'pekerjaan' => 'Karyawan',
                    'alamat' => 'Jl. Pendidikan No. 1',
                ],
            );

            $orangTua->siswa()->syncWithoutDetaching([
                $daftarSiswa[0]->id_siswa => ['hubungan' => 'ayah', 'is_kontak_utama' => true],
            ]);

            $this->buatIuranDanTransaksi($kelas, $daftarSiswa, $userBendahara->id_user);
            $this->buatDenah($kelas, $daftarSiswa, $userBendahara->id_user);
            $this->buatRuangan();
            $this->buatJadwal($kelas, $guru);
        });
    }

    /** @param array<int, Siswa> $daftarSiswa */
    private function buatIuranDanTransaksi(Kelas $kelas, array $daftarSiswa, int $idPencatat): void
    {
        $nominalIuran = 20000;

        for ($mundur = 2; $mundur >= 0; $mundur--) {
            $tanggal = now()->startOfMonth()->subMonths($mundur);
            $periode = $tanggal->format('Y-m');

            foreach ($daftarSiswa as $index => $siswa) {
                $lunas = $mundur > 0 || $index < 5;

                $iuran = IuranKas::firstOrCreate(
                    [
                        'id_kelas' => $kelas->id_kelas,
                        'id_siswa' => $siswa->id_siswa,
                        'periode' => $periode,
                    ],
                    [
                        'nominal' => $nominalIuran,
                        'status' => $lunas ? StatusIuran::Terverifikasi : StatusIuran::BelumBayar,
                        'diverifikasi_oleh' => $lunas ? $idPencatat : null,
                        'diverifikasi_pada' => $lunas ? $tanggal->copy()->day(10) : null,
                    ],
                );

                if ($lunas && ! $iuran->id_transaksi) {
                    $transaksi = TransaksiKas::create([
                        'id_kelas' => $kelas->id_kelas,
                        'jenis' => JenisKas::Pemasukan->value,
                        'kategori' => KategoriKas::IuranRutin->value,
                        'nominal' => $nominalIuran,
                        'tanggal' => $tanggal->copy()->day(10)->toDateString(),
                        'keterangan' => "Iuran kas periode {$periode} - {$siswa->nama_siswa}",
                        'id_siswa' => $siswa->id_siswa,
                        'dicatat_oleh' => $idPencatat,
                    ]);

                    $iuran->update(['id_transaksi' => $transaksi->id_transaksi]);
                }
            }
        }

        TransaksiKas::firstOrCreate(
            [
                'id_kelas' => $kelas->id_kelas,
                'keterangan' => 'Donasi kegiatan class meeting',
            ],
            [
                'jenis' => JenisKas::Pemasukan->value,
                'kategori' => KategoriKas::Donasi->value,
                'nominal' => 150000,
                'tanggal' => now()->subDays(5)->toDateString(),
                'dicatat_oleh' => $idPencatat,
            ],
        );

        TransaksiKas::firstOrCreate(
            [
                'id_kelas' => $kelas->id_kelas,
                'keterangan' => 'Pembelian perlengkapan kebersihan',
            ],
            [
                'jenis' => JenisKas::Pengeluaran->value,
                'kategori' => KategoriKas::Perlengkapan->value,
                'nominal' => 45000,
                'tanggal' => now()->subDays(3)->toDateString(),
                'dicatat_oleh' => $idPencatat,
            ],
        );

        TransaksiKas::firstOrCreate(
            [
                'id_kelas' => $kelas->id_kelas,
                'keterangan' => 'Konsumsi rapat kelas',
            ],
            [
                'jenis' => JenisKas::Pengeluaran->value,
                'kategori' => KategoriKas::Konsumsi->value,
                'nominal' => 75000,
                'tanggal' => now()->subDay()->toDateString(),
                'dicatat_oleh' => $idPencatat,
            ],
        );
    }

    /** @param array<int, Siswa> $daftarSiswa */
    private function buatDenah(Kelas $kelas, array $daftarSiswa, int $idPembuat): void
    {
        $denah = DenahKelas::firstOrCreate(
            ['id_kelas' => $kelas->id_kelas, 'berlaku_mulai' => now()->startOfMonth()->toDateString()],
            [
                'jumlah_baris' => 6,
                'jumlah_kolom' => 4,
                'posisi_meja_guru' => 'depan_tengah',
                'posisi_pintu' => 'kanan',
                'dibuat_oleh' => $idPembuat,
                'is_aktif' => true,
            ],
        );

        DenahKelas::where('id_kelas', $kelas->id_kelas)
            ->where('id_denah', '!=', $denah->id_denah)
            ->update(['is_aktif' => false]);

        if ($denah->posisiDuduk()->exists()) {
            return;
        }

        foreach ($daftarSiswa as $index => $siswa) {
            PosisiDuduk::create([
                'id_denah' => $denah->id_denah,
                'id_siswa' => $siswa->id_siswa,
                'baris' => intdiv($index, 4) + 1,
                'kolom' => ($index % 4) + 1,
            ]);
        }
    }

    private function buatRuangan(): void
    {
        $ruangan = [
            ['R-101', 'Ruang Kelas XI IPA 1', 'kelas', 'Gedung Utama', 1, 32, ['AC', 'Proyektor', 'Papan Tulis'], 1, 1, 6, 4, 'tersedia'],
            ['R-102', 'Ruang Kelas XI IPA 2', 'kelas', 'Gedung Utama', 1, 32, ['AC', 'Proyektor', 'Papan Tulis'], 8, 1, 6, 4, 'tersedia'],
            ['LAB-KOM', 'Lab Komputer', 'laboratorium', 'Gedung Utama', 2, 36, ['40 PC', 'AC', 'Proyektor'], 1, 1, 7, 4, 'tersedia'],
            ['PERPUS', 'Perpustakaan', 'perpustakaan', 'Gedung Utama', 2, 60, ['Rak Buku', 'Meja Baca', 'AC', 'WiFi'], 9, 1, 8, 4, 'tersedia'],
            ['RG', 'Ruang Guru', 'ruang_guru', 'Gedung Utama', 1, 25, ['Meja Kerja', 'AC', 'Sofa'], 15, 1, 6, 4, 'tersedia'],
            ['KANTIN', 'Kantin Sekolah', 'kantin', 'Gedung Pendukung', 1, 80, ['Meja Makan', 'Kios', 'Tempat Cuci Tangan'], 1, 1, 10, 5, 'tersedia'],
            ['UKS', 'Unit Kesehatan Sekolah', 'uks', 'Gedung Pendukung', 1, 10, ['Tempat Tidur', 'Obat-obatan', 'Kursi Roda'], 12, 1, 5, 4, 'tersedia'],
            ['TOILET', 'Toilet Bersama', 'toilet', 'Gedung Pendukung', 1, 20, ['Air Bersih', 'Sabun'], 18, 1, 4, 4, 'maintenance'],
        ];

        foreach ($ruangan as [$kode, $nama, $kategori, $gedung, $lantai, $kapasitas, $fasilitas, $x, $y, $lebar, $tinggi, $status]) {
            RuanganSekolah::updateOrCreate(
                ['kode_ruangan' => $kode],
                [
                    'nama_ruangan' => $nama,
                    'kategori' => $kategori,
                    'gedung' => $gedung,
                    'lantai' => $lantai,
                    'kapasitas' => $kapasitas,
                    'fasilitas' => $fasilitas,
                    'deskripsi' => "{$nama} untuk kegiatan belajar dan fasilitas sekolah.",
                    'posisi_x' => $x,
                    'posisi_y' => $y,
                    'lebar_grid' => $lebar,
                    'tinggi_grid' => $tinggi,
                    'status' => $status,
                    'jam_buka' => '07:00',
                    'jam_tutup' => '15:00',
                ],
            );
        }
    }

    private function buatJadwal(Kelas $kelas, Guru $guru): void
    {
        $mapel = MataPelajaran::firstOrCreate(
            ['kode_mapel' => 'MTK'],
            ['nama_mapel' => 'Matematika'],
        );

        $hari = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'][now()->dayOfWeek] ?? 'senin';

        JadwalPelajaran::firstOrCreate(
            [
                'id_kelas' => $kelas->id_kelas,
                'id_mapel' => $mapel->id_mapel,
                'id_guru' => $guru->id_guru,
                'semester' => 'ganjil',
                'hari' => $hari,
                'jam_mulai' => '07:00:00',
                'jam_selesai' => '15:00:00',
            ],
        );
    }
}
