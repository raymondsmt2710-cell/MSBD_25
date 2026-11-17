# Rencana Implementasi: Modul Kas & Denah

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:executing-plans (native). Langkah memakai checkbox.

**Goal:** Menambahkan modul kas kelas (transaksi, iuran, export) dan denah interaktif (kursi + area sekolah) ke proyek Laravel yang sudah ada.

**Architecture:** Perluas skema Indonesia yang sudah ada (`transaksi_kas`, `denah_kelas`, `posisi_duduk`), tambah `iuran_kas` dan `ruangan_sekolah`. Otorisasi lewat Gate + service `Akses`. UI Blade + Alpine dengan endpoint JSON untuk filter dan interaksi denah.

**Tech Stack:** Laravel 13.40, PHP 8.3, Blade, Alpine.js 3, Tailwind CSS 3, MySQL/SQLite (test).

**Spec:** `docs/superpowers/specs/2026-11-16-kas-dan-denah-design.md`

## Global Constraints

- Pertahankan penamaan tabel/kolom Indonesia dan primary key kustom.
- Hanya `admin` dan siswa dengan jabatan Bendahara yang boleh menulis kas.
- Hanya `admin` dan Ketua Kelas yang boleh mengatur kursi.
- Hanya `admin` yang boleh CRUD ruangan sekolah.
- Nominal selalu positif; transaksi tidak dihapus permanen (`is_batal`).
- Semua route kas/denah memakai middleware `auth`.

## Review Focus

- User tanpa data siswa/guru/orang tua tidak boleh menyebabkan error 500.
- Bendahara kelas A tidak boleh menulis ke kelas B (IDOR).
- Verifikasi iuran dua kali tidak boleh membuat dua transaksi.
- Swap kursi tidak boleh melanggar unique `(id_denah, baris, kolom)`.
- Ruangan dengan koordinat di luar kanvas tetap harus tampil tanpa merusak layout.

---

### Task 1: Skema, enum, model, akses

**Files:**
- Create: 4 migration di `database/migrations/2026_11_17_*`
- Create: `app/Enums/{JenisKas,StatusIuran,KategoriKas,KategoriRuangan,StatusRuangan}.php`
- Create: `app/Models/{TransaksiKas,IuranKas,RuanganSekolah,DenahKelas,PosisiDuduk,Kelas,Siswa,AnggotaKelas,PengurusKelas,Jabatan,TahunAjaran,Guru,OrangTua}.php`
- Modify: `app/Models/User.php`
- Create: `app/Support/Akses.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [x] Tulis migrasi, enum, model, relasi, service, Gate.
- [x] Jalankan `php artisan migrate:fresh` di SQLite lokal; pastikan tidak ada error.

### Task 2: Form request

**Files:**
- Create: `app/Http/Requests/Kas/*.php`, `app/Http/Requests/Denah/*.php`

- [x] Tulis aturan validasi sesuai tabel desain.
- [x] Pastikan `authorize()` memakai Gate yang tepat.

### Task 3: Controller kas

**Files:**
- Create: `app/Http/Controllers/Kas/{KasController,TransaksiKasController,IuranKasController,ExportKasController}.php`

- [x] Implementasi index/data/store/update/batal, generate/verifikasi/batal iuran, CSV/cetak.

### Task 4: Controller denah

**Files:**
- Create: `app/Http/Controllers/Denah/{DenahController,DenahKelasController,RuanganSekolahController}.php`

- [x] Implementasi data kursi, simpan layout, pindah, swap, acak; data ruangan, CRUD, status.

### Task 5: Route & navigasi

**Files:**
- Modify: `routes/web.php`, `resources/views/layouts/navigation.blade.php`

- [x] Daftarkan route dan link navigasi; perbaiki `Auth::user()->name` → `username`.

### Task 6: Frontend kas

**Files:**
- Create: `resources/js/kas.js`, `resources/views/kas/index.blade.php`, `resources/views/kas/_modal-transaksi.blade.php`, `resources/views/kas/cetak.blade.php`
- Modify: `resources/js/app.js`

- [x] Summary cards, filter, tabel, modal, matriks iuran, export, state Alpine.

### Task 7: Frontend denah

**Files:**
- Create: `resources/js/denah.js`, `resources/views/denah/index.blade.php`, 3 partial drawer/modal
- Modify: `resources/js/app.js`

- [x] Tab kursi + area sekolah, pencarian/highlight, drawer, mode edit, swap/acak, CRUD ruangan.

### Task 8: Seeder demo

**Files:**
- Create: `database/seeders/DemoSekolahSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [x] Isi tahun ajaran, kelas, siswa, jabatan, transaksi, iuran, denah, ruangan.

### Task 9: Test & verifikasi

**Files:**
- Create: `tests/Feature/KasTest.php`, `tests/Feature/DenahTest.php`

- [x] Test otorisasi, ringkasan, iuran idempoten, swap, acak, CRUD ruangan.
- [x] Jalankan `php artisan test`; semua lulus.
- [x] Jalankan `npm install && npm run build`; build lulus.
