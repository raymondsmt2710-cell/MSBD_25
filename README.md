# Sistem E-Learning Berbasis Web pada SMAN 1 Kabanjahe

Tugas Besar Mata Kuliah **Manajemen Sistem Basis Data**
Fakultas Ilmu Komputer dan Teknologi Informasi, Universitas Sumatera Utara

> **Status:** Tahap 1 – Perencanaan (Bab I & II)

---

## Deskripsi Proyek

SMAN 1 Kabanjahe belum memiliki sistem pembelajaran digital yang terpadu. Kegiatan penunjang pembelajaran seperti presensi, pencatatan kas kelas, dan informasi kelas masih dikelola secara manual dan terpisah-pisah. Proyek ini merancang sebuah **sistem e-learning berbasis web** dengan **basis data terpusat** yang terstruktur, konsisten, dan aman untuk mendukung kegiatan sekolah.

## Fitur Utama

| No | Fitur | Deskripsi Singkat |
| --- | --- | --- |
| 1 | Presensi barcode sekali pakai | Pencatatan kehadiran dengan barcode sekali pakai, dilengkapi wadah pengajuan alasan ketidakhadiran |
| 2 | Roster kelas digital | Penyajian daftar siswa dan jadwal kelas secara digital |
| 3 | Pemilihan suara elektronik | Pemungutan dan rekapitulasi suara Badan Pengurus Harian organisasi sekolah |
| 4 | Pencatatan kas kelas | Pencatatan pemasukan dan pengeluaran kas kelas secara transparan |
| 5 | Perangkat organisasi dan kelas | Penyajian struktur dan peran pengurus kelas serta organisasi |
| 6 | Denah kelas | Penyajian tata letak tempat duduk siswa |
| 7 | Pemantauan orang tua/wali | Akun hanya-baca untuk melihat status dan riwayat kehadiran anak |

## Pengguna Sistem

- Siswa
- Bendahara kelas
- Guru / wali kelas
- Pengurus organisasi / panitia pemilihan
- Administrator sistem
- Orang tua/wali siswa

## Batasan Masalah

- Tidak mencakup pengelolaan nilai akademik siswa
- Presensi hanya untuk siswa (bukan guru/staf)
- Pencatatan keuangan terbatas pada kas kelas
- Pemantauan orang tua/wali bersifat hanya-baca dan tanpa notifikasi di luar aplikasi (SMS/aplikasi pesan)
- Ruang lingkup terbatas pada SMAN 1 Kabanjahe

## Teknologi yang Direncanakan

- **Framework:** Laravel (PHP)
- **DBMS:** MySQL / PostgreSQL (relasional)
- **Aplikasi:** Web (dapat diakses melalui peramban, responsif untuk ponsel)
- **Pendukung:** Pustaka pembuatan dan pembacaan barcode, perangkat desain diagram (ERD & DFD)

## Instalasi dan Menjalankan Proyek

```bash
# 1. Clone repository
git clone <url-repository>
cd <nama-folder>

# 2. Pasang dependensi
composer install
npm install

# 3. Salin dan atur konfigurasi environment
cp .env.example .env
php artisan key:generate

# 4. Atur koneksi database pada file .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 5. Jalankan migrasi dan seeder
php artisan migrate --seed

# 6. Jalankan aplikasi
npm run dev
php artisan serve
```

Aplikasi dapat diakses di `http://localhost:8000`.

## Metodologi Pengembangan

Model **Waterfall** (SDLC) dengan tahapan:

1. Perencanaan (Bab I & II)
2. Analisis (Bab III)
3. Perancangan (Bab III)
4. Implementasi (Bab IV)
5. Pengujian (Bab IV)
6. Pemeliharaan (Bab V)

## Rencana Proyek

| Tahap | Waktu | Milestone |
| --- | --- | --- |
| Perencanaan | Minggu 1–2 | Laporan Bab I dan II |
| Analisis | Minggu 3 | Dokumen kebutuhan dan DFD |
| Perancangan | Minggu 4–5 | ERD dan desain tabel final |
| Implementasi | Minggu 6 | Basis data dan aplikasi berjalan |
| Pengujian dan evaluasi | Minggu 7 | Hasil uji dan evaluasi |
| Laporan akhir | Minggu 8 | Laporan akhir |

## Anggota Kelompok

**Kelompok 2 – Kelas KOM A**
Dosen Pengampu: Dedy Arisandi S.T., M.Kom.

| No. | Nama | NIM | GitHub |
| --- | --- | --- | --- |
| 1 | Ryan Fredryck Ginting | 251402075 | [@RynFrr](https://github.com/RynFrr) |
| 2 | Raymond Ganda Parsaoran Simarmata | 251402078 | [@raymondsmt2710-cell](https://github.com/raymondsmt2710-cell) |
| 3 | Charles | 251402081 | [@kamitsuki18384](https://github.com/kamitsuki18384) |
| 4 | Alvin Benyamin Sigalingging | 251402113 | [@41vin-cyber](https://github.com/41vin-cyber) |
| 5 | Ryan Dani Stepanus Girsang | 251402140 | [@ryan25-girsang](https://github.com/ryan25-girsang) |

## Informasi Akademik

- **Mata Kuliah:** Manajemen Sistem Basis Data
- **Periode:** Semester Ganjil 2026/2027
- **Kelompok:** 2
- **Institusi:** Universitas Sumatera Utara, Medan

## Struktur Repository

Struktur mengikuti standar Laravel, dengan tambahan folder `docs/` dan `sql/`.

```
├── app/                # Model, controller, middleware, dan logika aplikasi
├── bootstrap/
├── config/             # Konfigurasi aplikasi
├── database/
│   ├── migrations/     # Skema tabel (DDL)
│   ├── seeders/        # Data awal (DML)
│   └── factories/
├── docs/               # Laporan, diagram konteks, DFD, ERD, kamus data
├── public/
├── resources/
│   └── views/          # Tampilan (Blade)
├── routes/             # Definisi rute (web.php)
├── sql/                # Skrip SQL manual: DCL, TCL, view, trigger, prosedur
├── storage/
├── tests/              # Pengujian fungsional
├── .env.example
├── composer.json
└── README.md
```

---

© 2026 Kelompok 2 – Universitas Sumatera Utara
