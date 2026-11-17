# Fitur Khusus Administrator

Panel admin ada di `/admin` (rute `admin.*`, berkas `routes/admin.php`), hanya untuk role `admin`.
Admin yang login langsung diarahkan ke dasbor `/admin`.

| No | Fitur | Halaman | Controller |
|---|---|---|---|
| 1 | Kelola akun pengguna | `/admin/akun` | `Admin\AkunController` |
| 2 | Data siswa | `/admin/siswa` | `Admin\SiswaController` |
| 3 | Data guru | `/admin/guru` | `Admin\GuruController` |
| 4 | Orang tua & relasi ke siswa | `/admin/orang-tua` | `Admin\OrangTuaController` |
| 5 | Tahun ajaran | `/admin/tahun-ajaran` | `Admin\TahunAjaranController` |
| 6 | Mata pelajaran | `/admin/mapel` | `Admin\MapelController` |
| 7 | Master jabatan | `/admin/jabatan` | `Admin\JabatanController` |
| 8–9 | Kelas, wali kelas, roster | `/admin/kelas`, `/admin/kelas/{id}` | `Admin\KelasController` |
| 10 | Jadwal pelajaran | `/admin/jadwal` | `Admin\JadwalController` |
| 11 | Organisasi sekolah | `/admin/organisasi` | `Admin\OrganisasiController` |
| 12 | Pengurus kelas & organisasi | `/admin/pengurus` | `Admin\PengurusController` |
| 13–17 | Pemilihan | `/kelola/pemilihan` (modul yang sudah ada) | `KelolaPemilihanController` |
| 18 | Presensi semua kelas (hanya-baca) | `/admin/presensi` | `Admin\PresensiController` |
| 19 | Kas semua kelas | `/admin/kas` | `Admin\KasController` |
| 20 | Denah semua kelas | `/admin/denah` | `Admin\DenahController` |

## Alur membuat pengguna baru

1. **Akun** → *Akun Baru* (username, peran, password; kosongkan password agar dibuatkan otomatis — ditampilkan sekali).
2. Untuk siswa/guru/orang tua, sistem langsung membuka form profilnya dengan akun tersebut terpilih.
3. Orang tua: setelah profil disimpan, hubungkan ke siswa (ayah/ibu/wali, kontak utama).

## Aturan bisnis yang dijaga

- **Akun tidak dihapus**, hanya dinonaktifkan. Middleware `PastikanAkunAktif` (kini terdaftar di grup `web`) langsung memutus session akun yang dinonaktifkan.
- Admin tidak bisa menonaktifkan/mengubah peran dirinya sendiri, dan admin aktif terakhir tidak bisa dinonaktifkan.
- Peran akun yang sudah punya profil terkunci.
- Siswa tidak dihapus; gunakan status `lulus/pindah/keluar` (opsional sekaligus menonaktifkan akun).
- Satu siswa paling banyak satu ayah dan satu ibu; kontak utama hanya satu per siswa.
- Tepat satu tahun ajaran aktif; periode tahun ajaran tidak boleh tumpang-tindih.
- Nama kelas unik per tahun ajaran; guru wali paling banyak satu kelas per tahun ajaran;
  siswa hanya di satu kelas per tahun ajaran; nomor absen unik per kelas.
- Jadwal: bentrok jam di kelas yang sama atau guru yang sama (tahun ajaran & semester sama) ditolak.
- Pengurus: lingkup jabatan harus cocok, pengurus kelas wajib anggota kelas, kuota jabatan dijaga
  (dengan `lockForUpdate`), satu siswa satu jabatan per kelas / per organisasi per tahun ajaran.
- Penghapusan yang akan memicu `ON DELETE CASCADE` ke data riwayat (kelas berisi, jadwal yang sudah
  punya sesi presensi, organisasi yang pernah punya pemilihan, tahun ajaran yang dipakai) ditolak dengan pesan.
- Kas: transaksi tidak pernah dihapus. Pembatalan wajib beralasan dan mencatat siapa & kapan
  (migration `2026_11_18_000001_tambah_audit_pembatalan_kas`). Saldo tidak menghitung transaksi batal.
- Presensi admin **hanya-baca**: kolom `dibuka_oleh`, `diubah_oleh`, `diverifikasi_oleh` merujuk `guru`.
- **Anonimitas pemilihan**: tidak ada kode admin yang menyentuh tabel `suara`
  (dijaga oleh tes `HalamanAdminTest::test_kode_admin_tidak_menyentuh_tabel_suara`).

## Pengujian

```bash
php artisan test tests/Feature/Admin
```
