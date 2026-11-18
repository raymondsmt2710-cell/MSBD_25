# Desain: Tampilan Mengikuti Situs SMA Negeri 1 Kabanjahe

Tanggal: 2026-11-17
Status: menunggu persetujuan
Stack: Laravel 12/13, PHP 8.5, Blade, Alpine.js 3, Tailwind CSS 3, Vite 8, MySQL 8.4 (Laragon)

---

## 1. Ringkasan

Memperbarui seluruh tampilan halaman yang sudah aktif — login, presensi guru (jadwal & sesi),
presensi siswa (riwayat & konfirmasi), dan halaman beranda — agar mengikuti identitas visual
situs resmi sekolah <https://sman1kabanjahe.sch.id/>. Satu komponen layout bersama dipakai semua
halaman, sehingga halaman kas dan denah yang sudah memakai `<x-app-layout>` ikut serasi tanpa
ditulis ulang.

Aplikasi dikembalikan ke database yang dimaksud proyek (MySQL, sesuai `.env.example` dan README),
karena halaman presensi bergantung pada view/fungsi/prosedur MySQL. Sekaligus memperbaiki bug
yang menghalangi login dan pembukaan halaman:

1. `DemoSekolahSeeder` menulis password plaintext ke `password_hash` dan berhenti di
   `$daftarSiswa[1]->user` karena `Siswa` tidak punya relasi `user()`.
2. `CURDATE()` di `PresensiGuruController` diganti bentuk portabel (tidak wajib untuk MySQL, tetapi
   membuat query konsisten dan aman bila driver lain dipakai).

Akun `raymond` tetap dipakai untuk verifikasi lokal; database bersih memakai akun `admin` hasil
seeder.

## 2. Tujuan dan non-tujuan

**Tujuan**

- Halaman aktif terlihat seperti bagian dari situs sekolah: palet teal/amber, font Figtree,
  kartu membulat, ikon garis, responsif di ponsel.
- Satu sumber layout (`<x-app-layout>`) untuk semua halaman setelah login, dan satu layout tamu
  untuk halaman login.
- Aplikasi berjalan di MySQL yang menjadi target proyek, sehingga presensi guru dan siswa
  benar-benar bisa dibuka dan diuji.
- Akun hasil seeder selalu bisa login dengan password `password`, termasuk pada database kosong.

**Non-tujuan**

- Tidak menambah/mengubah route. Kas dan denah tetap belum terhubung.
- Tidak mengubah/tulis ulang logika bisnis presensi, kas, atau denah.
- Tidak mem-port view/fungsi/prosedur MySQL ke PHP atau SQLite.
- Tidak menulis ulang isi internal halaman kas/denah; mereka hanya mewarisi header, footer, dan
  warna dasar dari layout.
- Tidak menambah dark mode, halaman lupa password, atau fitur "ingat saya" (backend belum
  mendukung).
- Test `KasTest`/`DenahTest` yang sudah gagal sebelum pekerjaan ini (route kas/denah belum ada)
  tidak dijadikan syarat selesai.

## 3. Kondisi saat ini

| Hal | Keadaan |
|---|---|
| Layout | `layouts/app.blade.php` memakai CSS inline, header biru `#1e3a8a` |
| Komponen layout | `app/View/Components/AppLayout.php` dan `GuestLayout.php` adalah class component dan **mengalahkan** komponen Blade; keduanya merender `layouts.app`/`layouts.guest` lama |
| Halaman aktif | login, guru/presensi (index, sesi), siswa/presensi (riwayat, konfirmasi) memakai `@extends('layouts.app')` |
| Kas, denah, dashboard\* | sudah memakai `<x-app-layout>` + Tailwind, dirender oleh class component lama |
| Font | Figtree sudah diatur di `tailwind.config.js`, belum dipakai layout lama |
| Aset | `public/build` ada, tetapi `public/hot` menunjuk dev server `[::1]:5173` yang tidak jalan |
| Teks | Beberapa view presensi mengandung karakter rusak (`�?"`, `��`) akibat encoding |
| Login | redirect admin ke `/` (welcome bawaan Laravel) |
| Database | `.env` memakai SQLite, padahal `.env.example`/README menargetkan MySQL; halaman presensi memakai view `v_rekap_presensi_siswa`, fungsi `fn_token_valid`/`fn_persentase_kehadiran`/`fn_jumlah_presensi`, dan prosedur `sp_buka_sesi`/`sp_buat_token_presensi`/`sp_scan_presensi`/`sp_tutup_sesi` yang hanya dibuat saat migrasi di MySQL |
| MySQL | Service `MySQL80` *disabled* (butuh admin); MySQL 8.4 Laragon di `C:\laragon\bin\mysql\...` dengan data di `C:\laragon\data\mysql-8.4` dapat dijalankan tanpa admin |
| Seeder | `DemoSekolahSeeder` menulis `password_hash = 'password'` (plaintext) dan gagal di `$daftarSiswa[1]->user`; `Siswa` tidak punya relasi `user()` |

## 4. Referensi visual

Diambil dari situs sekolah (yang juga Laravel + Tailwind + Alpine):

- Teal utama `#09656F`, hover `#07535C`, teal sangat gelap `#043D44`
- Amber aksen `#C97A14`, gelap `#7A4A0C`
- Tinta gelap `#1E3335`, biru pendukung `#1B5E8E`
- Latar putih dan `slate-50`; teks `gray-800`/`gray-500`
- Figtree 400–800
- Kartu `rounded-2xl`/`rounded-3xl`, bayangan halus, label kapital kecil ber-tracking lebar,
  ikon 20px di dalam input, tombol utama teal penuh
- Halaman login: latar gradasi `#1E3335 → #09656F → #1B5E8E` dengan bulatan blur, kartu putih
  di tengah, kotak ikon teal di atas judul

Logo resmi sekolah diambil dari situs referensi dan disimpan di
`public/images/logo-sekolah.png` (sekolah yang sama, jadi logo yang tepat).

## 5. Pendekatan

| Pendekatan | Keterangan | Keputusan |
|---|---|---|
| **A. Tailwind + layout bersama** | Token warna di `tailwind.config.js`, komponen `x-app-layout` dan `x-guest-layout`, halaman ditulis ulang dengan kelas Tailwind, build Vite | **Dipilih** |
| B. CSS inline | Pertahankan `layouts/app.blade.php`, ganti palet dan susun ulang HTML | Ditolak: terpisah dari kas/denah, CSS manual banyak |
| C. Campuran | Login/dashboard Tailwind, presensi tetap inline | Ditolak: tampilan tidak konsisten |

Alasan A: paling dekat dengan referensi, menyatukan semua halaman, dan sejalan dengan arah
kas/denah yang sudah Tailwind.

## 6. Desain

### 6.1 Token warna

Tambahan di `tailwind.config.js`:

```js
colors: {
  sekolah: {
    teal: '#09656F', 'teal-dark': '#07535C', 'teal-deep': '#043D44',
    amber: '#C97A14', 'amber-dark': '#7A4A0C',
    ink: '#1E3335', blue: '#1B5E8E',
  },
}
```

Font `sans` tetap Figtree.

### 6.2 Komponen layout

**`resources/views/components/app-layout.blade.php`** — dipakai semua halaman setelah login.

- `@props(['judul' => null])`; `<title>` = judul + "– E-Learning SMAN 1 Kabanjahe".
- Kepala halaman putih lengket (sticky): logo + wordmark "E-Learning / SMA Negeri 1 Kabanjahe".
- Navigasi sesuai role, hanya tautan yang route-nya ada:
  admin → Beranda; guru → Beranda, Jadwal Presensi; siswa → Beranda, Riwayat Presensi;
  orang_tua → Beranda.
- Sisi kanan: nama pengguna + label role (chip), tombol "Keluar" (teal outline), menu ponsel
  memakai Alpine.
- Slot `header` opsional (dipakai kas/denah): blok judul halaman dengan latar putih, garis bawah.
- Area isi `max-w-7xl`, latar `bg-slate-50`.
- Flash `success`/`error` sebagai alert `rounded-xl` hijau/merah dengan ikon.
- Footer tipis: "© 2026 E-Learning SMA Negeri 1 Kabanjahe".
- Memuat aset dengan `@vite(['resources/css/app.css', 'resources/js/app.js'])`.

**`resources/views/components/guest-layout.blade.php`** — halaman login (dan halaman tamu lain
nanti).

- Latar gradasi teal penuh layar + tiga bulatan blur amber/teal.
- Kartu putih `max-w-md`, `rounded-3xl`, bayangan besar, padding 8.
- Slot untuk isi; `judul` untuk `<title>`.

`app/View/Components/AppLayout.php` dan `GuestLayout.php` dihapus (class component menang atas
komponen Blade dengan nama sama). Setelah semua view pindah, `layouts/app.blade.php`,
`layouts/guest.blade.php`, dan `layouts/navigation.blade.php` juga dihapus. Komponen
`components/*.blade.php` bawaan Breeze yang tidak dipakai dibiarkan.

### 6.3 Halaman

**Login (`auth/login.blade.php`)** memakai `x-guest-layout`:

- Logo 64px dalam kotak teal `rounded-2xl`, judul "E-Learning", subjudul sekolah, garis pemisah.
- "Selamat Datang" + "Masuk ke akun Anda untuk melanjutkan".
- Input username dan password dengan ikon di kiri, label kapital, fokus ring teal.
- Pesan error `Username atau password salah.` dalam alert merah membulat.
- Tombol "Masuk" teal penuh dengan ikon gembok.

**Beranda (`welcome.blade.php`)**:

- Hero gradasi seperti login dengan logo, nama aplikasi, dan satu kalimat sambutan.
- Tamu: tombol "Masuk". Sudah login: sapaan, chip role, tautan cepat sesuai role, tombol keluar.
- Tidak ada tautan ke halaman yang belum punya route.

**Presensi guru — jadwal (`guru/presensi/index.blade.php`)**:

- Judul "Jadwal Mengajar" + tanggal hari ini.
- Tabel dalam kartu: Hari, Jam, Kelas, Mata Pelajaran, Sesi Hari Ini.
- Baris hari ini disorot teal muda; tombol "Mulai Presensi" teal; sesi aktif jadi tautan
  "Buka sesi (status)"; hari lain tanda "—".
- Keadaan kosong: ilustrasi ikon + "Belum ada jadwal."

**Presensi guru — sesi (`guru/presensi/sesi.blade.php`)**:

- Dua kartu: (1) QR 320px, hitungan "hadir" besar, catatan amber "QR berganti otomatis",
  tombol "Tutup Sesi" merah dengan konfirmasi; (2) tabel kehadiran dengan badge status
  (hadir hijau, izin/sakit amber, alpa merah, belum abu) dan form koreksi.
- JavaScript polling QR tetap, hanya id/kelas disesuaikan.

**Presensi siswa — riwayat (`siswa/presensi/riwayat.blade.php`)**:

- Empat kartu ringkasan: Persentase, Hadir, Izin/Sakit, Alpa dengan angka besar.
- Catatan memindai QR.
- Tabel riwayat dengan badge status dan pagination bergaya Tailwind.

**Presensi siswa — konfirmasi (`siswa/presensi/konfirmasi.blade.php`)**:

- Kartu tengah `max-w-md`; QR valid: ikon centang teal, tombol "Saya Hadir"; tidak valid: ikon
  peringatan amber, penjelasan, tautan "Lihat Riwayat".

Semua karakter rusak (`�?"`, `��`) diganti `–`, `—`, dan `·` yang benar.

### 6.4 Aset dan build

- Unduh logo dari situs sekolah ke `public/images/logo-sekolah.png`.
- Hapus `public/hot` yang basi (sudah di `.gitignore`) agar aset hasil build dipakai.
- Jalankan `npm run build`; `public/build` juga di-`.gitignore`, jadi build diulang di mesin lain.
- Layout memakai `@vite`, jadi `npm run dev` tetap bisa dipakai saat mengembangkan tampilan.

### 6.5 Database MySQL

- Nyalakan MySQL Laragon (tanpa admin):
  `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe --defaults-file=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini`
  sebagai proses latar; pastikan port 3306 menerima koneksi.
- Buat database `elearning_smansa` (utf8mb4) memakai user `root` Laragon.
- Ubah `.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=elearning_smansa`, `DB_USERNAME=root`,
  `DB_PASSWORD=` (sesuai instalasi Laragon), plus kredensial role `DB_USERNAME_ADMIN/GURU/SISWA/ORTU`
  dan password-nya (nilai dev, mis. `admin_app`/`admin_app`).
- `php artisan migrate --seed`, lalu `php artisan db:seed --class=DemoSekolahSeeder`.
  Migrasi MySQL membuat view, fungsi, prosedur, trigger, dan user MySQL per role.
- Buat akun `raymond` (admin, password `password`) lewat tinker setelah seeding.
- `database/database.sqlite` lama tidak dihapus (cadangan).
- `php artisan serve` perlu dijalankan ulang setelah `.env` berubah.

### 6.6 Perbaikan backend

1. `app/Http/Controllers/PresensiGuruController.php`:
   - `whereRaw('sp.tanggal = CURDATE()')` → `whereDate('sp.tanggal', now()->toDateString())`.
   - `orderByRaw("FIELD(jp.hari, ...)")` → `orderByRaw("CASE jp.hari WHEN 'senin' THEN 1 ... ELSE 7 END")`
     agar query (dan test di SQLite) tidak bergantung pada fungsi MySQL.
2. `app/Models/Siswa.php`: tambah relasi `user()` (`belongsTo(User::class, 'id_user', 'id_user')`)
   agar `DemoSekolahSeeder` tidak crash.
3. `database/seeders/DemoSekolahSeeder.php`:
   - satu `$hash = Hash::make('password')` dipakai untuk `guru1`, `siswa1..8`, `ortu1`;
   - user yang sudah ada tetapi `password_hash`-nya bukan hash (plaintext lama) diperbarui ke
     `$hash`; user dengan hash valid tidak disentuh (password yang sudah diganti tidak direset).
4. Test regresi:
   - `tests/Feature/PresensiGuruIndexTest.php`: guru dengan jadwal hari ini membuka
     `/guru/presensi` → 200; jalankan di SQLite in-memory. Gagal sebelum perbaikan `CURDATE`/`FIELD`.
   - `tests/Feature/SeederPasswordTest.php`: jalankan `DemoSekolahSeeder` di database test, lalu
     `Hash::check('password', ...)` untuk `guru1`, `siswa5`, dan `ortu1`.
   - `tests/Feature/DBDynamicConnectionTest.php` yang sudah ada tetap lulus.

## 7. Yang diserahkan ke builder

- Menyiapkan MySQL lokal, membuat database, menyesuaikan `.env`, migrasi, dan seeding.
- Menulis dua komponen layout, lima view di atas, token Tailwind, dan aset logo.
- Menghapus class component layout lama setelah komponen Blade baru terbukti dipakai.
- Memperbaiki query presensi, relasi `Siswa::user()`, dan seeder demo.
- Menjaga seluruh teks dan route yang ada; tidak menambah route.
- Menjalankan `npm run build`, test regresi, dan pemeriksaan manual.

## 8. Verifikasi

- MySQL menerima koneksi di port 3306 dan `php artisan migrate:status` bersih.
- `npm run build` sukses.
- `php artisan test --filter='PresensiGuruIndexTest|SeederPasswordTest|DBDynamicConnectionTest'`
  lulus.
- Manual lewat `php artisan serve`, memakai data MySQL:
  - login `raymond`/`password` → beranda tampil bergaya baru.
  - login `guru1`/`password` → jadwal presensi 200 dan tabel tampil.
  - login `siswa1`/`password` → riwayat presensi 200 (view MySQL terbaca).
  - halaman login di lebar ponsel (±375px) tetap rapi.
- Halaman kas/denah tidak diuji karena route-nya belum ada.

## 9. Risiko

- MySQL Laragon bisa punya password `root` non-kosong; bila koneksi gagal, builder berhenti dan
  meminta kredensial, bukan menebak.
- Migrasi membuat user MySQL per role; bila gagal, `DBDynamicConnection` jatuh ke koneksi bawaan
  (perilaku yang sudah diperbaiki) sehingga aplikasi tetap bisa dibuka.
- Perubahan `.env` butuh `php artisan serve` dijalankan ulang.
- Tailwind 3 + Vite 8: bila build gagal, layout tidak ter-style. Mitigasi: build dijalankan dan
  diperiksa sebelum selesai.
- `@vite` membutuhkan manifest; `public/hot` basi dihapus agar tidak menunjuk dev server mati.
- Menghapus `layouts/app.blade.php` hanya setelah tidak ada view yang memakainya.
- Perubahan query presensi menyentuh halaman guru; test regresi menjaga perilaku.
