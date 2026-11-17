# Panduan Fitur Pemilihan Suara Elektronik (e-Voting)

Fitur #3 pada `AGENTS.md`: pemilihan pengurus organisasi sekolah. Admin membuat pemilihan dan
menunjuk panitia; panitia mengelola pemilihan yang ditugaskan; siswa memilih tepat satu kali,
dan hasil terbuka setelah pemilihan ditutup.

Rancangan lengkap: `docs/superpowers/specs/2026-11-17-voting-design.md`
Rencana kerja: `docs/superpowers/plans/2026-11-17-voting-plan.md`

---

## 1. Cara membuka

```bash
php artisan migrate          # membuat tabel, procedure, function, view, trigger, GRANT
php artisan db:seed --class=PemilihanSeeder   # data contoh (idempoten, aman diulang)
php artisan storage:link     # agar foto kandidat dapat diakses
php artisan serve
```

Buka <http://localhost:8000>.

### Akun contoh (password semuanya `password`)

| Username | Role | Bisa apa |
| --- | --- | --- |
| `admin` | admin | Semua: buat pemilihan, kandidat, pemilih, panitia, buka/tutup |
| `guru1` | guru | Panitia pemilihan contoh: kandidat, pemilih, monitor, buka/tutup (tidak bisa **membuat** pemilihan baru) |
| `siswa1` … `siswa9` | siswa | Melihat pemilihan yang diikuti, memilih, melihat hasil |

### Alamat halaman

| Halaman | URL |
| --- | --- |
| Daftar pemilihan (siswa) | `/siswa/pemilihan` |
| Memilih | `/siswa/pemilihan/{id}/pilih` |
| Hasil (setelah selesai) | `/siswa/pemilihan/{id}/hasil` |
| Kelola pemilihan | `/kelola/pemilihan` |
| Monitor / buka / tutup | `/kelola/pemilihan/{id}/monitor` |
| Kandidat | `/kelola/pemilihan/{id}/kandidat` |
| Daftar pemilih | `/kelola/pemilihan/{id}/pemilih` |
| Panitia (admin saja) | `/kelola/pemilihan/{id}/panitia` |
| Log audit | `/kelola/pemilihan/{id}/log` |

---

## 2. Alur uji manual

1. Login `admin` → `/kelola/pemilihan` → **Buat Pemilihan**. Statusnya `draf`.
2. Buka **Kandidat** → tambah kandidat (nomor urut, siswa ketua, wakil opsional, visi, misi, foto).
3. Buka **Pemilih** → pilih satu atau beberapa kelas → **Daftarkan**.
   Semua siswa berstatus `aktif` di kelas itu otomatis menjadi pemilih.
4. Buka **Monitor** → **Buka Pemilihan**.
5. Login `siswa1` → `/siswa/pemilihan` → **Pilih Sekarang** → pilih kandidat →
   konfirmasi **Ya, Kirim Suara**. Muncul pesan suara berhasil direkam.
6. Coba pilih lagi: ditolak dengan pesan "Anda sudah memberikan suara".
7. Login `admin` → Monitor: hitungan sementara bertambah. Partisipasi naik.
8. **Tutup Pemilihan** → login `siswa1` → **Lihat Hasil**.

---

## 3. Objek database yang dibuat

### Stored Procedure

| Nama | Fungsi |
| --- | --- |
| `sp_rekam_suara` | **Satu-satunya** jalan masuk suara. Memeriksa status, rentang waktu, keanggotaan pemilih, kandidat, dan mencegah suara ganda. |
| `sp_simpan_pemilihan` | Buat/ubah pemilihan (hanya saat `draf`). Membuat pemilihan baru hanya lewat aplikasi oleh admin; procedure sendiri masih menerima admin atau panitia. |
| `sp_simpan_kandidat` | Buat/ubah kandidat (hanya saat `draf`). |
| `sp_hapus_kandidat` | Hapus kandidat (ditolak bila sudah ada suara). |
| `sp_daftarkan_pemilih_kelas` | Daftarkan semua siswa aktif satu kelas. Idempoten. |
| `sp_hapus_pemilih` | Hapus satu pemilih (ditolak bila sudah memilih). |
| `sp_tunjuk_panitia` | Tunjuk/cabut panitia. Hanya admin. |
| `sp_buka_pemilihan` | `draf` → `berlangsung`. Menolak bila tanpa kandidat/pemilih. |
| `sp_tutup_pemilihan` | `berlangsung` → `selesai`. |
| `sp_kembalikan_ke_draf` | `berlangsung` → `draf`, ditolak bila sudah ada suara. |

### Stored Function

| Nama | Fungsi |
| --- | --- |
| `fn_sudah_memilih(id_pemilihan, id_siswa)` | 1 bila siswa sudah memilih. |
| `fn_boleh_memilih(id_pemilihan, id_siswa)` | 1 bila boleh memilih sekarang. |
| `fn_total_suara(id_pemilihan)` | Jumlah suara sah. |
| `fn_partisipasi(id_pemilihan)` | Persentase pemilih yang sudah memilih. |

### View

| Nama | Isi |
| --- | --- |
| `v_kandidat_pemilihan` | Kandidat tanpa kolom suara (untuk siswa). |
| `v_kandidat_pemilihan_hasil` | Kandidat + jumlah suara (guru/admin saja). |
| `v_hasil_pemilihan` | Rekap hasil; **angka `NULL` bila status belum `selesai`**. |
| `v_partisipasi_pemilihan` | Total pemilih, sudah memilih, persentase. |
| `v_pemilihan_pengelola` | Daftar pemilihan untuk halaman kelola. |

### Trigger

| Nama | Fungsi |
| --- | --- |
| `trg_before_suara_insert` | Menolak suara bila pemilihan tidak `berlangsung` atau kandidat bukan miliknya. |
| `trg_before_suara_update` | Menolak perubahan suara. |
| `trg_before_suara_delete` | Menolak penghapusan suara. |
| `trg_before_pemilihan_delete` | Menolak hapus pemilihan yang sudah punya suara (menutup jalan pintas lewat `ON DELETE CASCADE`). |
| `trg_before_kandidat_insert` / `trg_before_kandidat_update` | Kandidat hanya boleh ditambah/diubah saat pemilihan `draf`. |
| `trg_before_kandidat_delete` | Menolak hapus kandidat yang sudah punya suara. |
| `trg_before_daftar_pemilih_insert` | Pemilih hanya boleh didaftarkan saat pemilihan `draf`. |
| `trg_before_daftar_pemilih_update` | Menolak pengembalian `sudah_memilih` ke 0 (anti suara ganda). |
| `trg_before_daftar_pemilih_delete` | Menolak hapus pemilih yang sudah memilih. |
| `trg_before_pemilihan_update` | Mengunci data pemilihan setelah status bukan `draf`, dan hanya mengizinkan transisi status yang sah. |
| `trg_after_pemilihan_update` | Mencatat perubahan status ke `log_pemilihan`. |
| `trg_after_daftar_pemilih_update` | Mencatat keikutsertaan pemilih (tanpa menyebut kandidat). |

### Index

- `idx_daftar_pemilih_status` — `daftar_pemilih (id_pemilihan, sudah_memilih)`
- `idx_suara_pemilihan_kandidat` — `suara (id_pemilihan, id_kandidat)`
- `idx_pemilihan_status_waktu` — `pemilihan (status, waktu_mulai, waktu_selesai)`

Verifikasi: `EXPLAIN SELECT id_kandidat, COUNT(*) FROM suara WHERE id_pemilihan = 1 GROUP BY id_kandidat`
memakai `idx_suara_pemilihan_kandidat`.

---

## 4. Kenapa suara tidak bisa dipalsukan

Tiga lapis, dan yang menentukan ada di database:

1. **GRANT.** `siswa_app` **tidak** punya `INSERT` ke `suara` dan **tidak** punya `UPDATE` ke
   `daftar_pemilih`. Satu-satunya cara siswa menghasilkan suara adalah
   `EXECUTE sp_rekam_suara`. Walaupun aplikasi punya bug, MySQL menolak.
2. **Procedure.** `UPDATE daftar_pemilih ... AND sudah_memilih = 0` lalu periksa
   `ROW_COUNT()`. Dua permintaan bersamaan hanya bisa menghasilkan satu suara.
3. **Trigger.** Menolak suara di luar status `berlangsung`, menolak perubahan/penghapusan
   suara, dan menolak perubahan data pemilihan setelah dibuka.

## 5. Kenapa suara tetap rahasia

- Tabel `suara` **tidak** punya kolom `id_siswa`. Tidak ada kolom yang menghubungkan
  pemilih dengan kandidat pilihannya.
- `suara.id_suara` dibuat sebagai UUID v4 acak (`RANDOM_BYTES`), **bukan** `UUID()` MySQL
  yang berbasis waktu. Dengan UUID v1, urutan baris bisa dicocokkan dengan `waktu_memilih`
  dan pilihan tiap siswa bisa direkonstruksi.
- `log_pemilihan` mencatat "siswa X sudah memilih", tidak pernah menyebut kandidat.
- `v_hasil_pemilihan` mengembalikan `NULL` selama status belum `selesai`, sehingga siswa
  tidak bisa mengintip hitungan sementara walau membaca langsung ke database.

## 6. Batas yang perlu diketahui

- Administrator database yang login langsung sebagai `admin_app` (yang masih memegang
  `GRANT ALL ON msbd_25.*` dari migration presensi) secara teknis masih bisa menghapus
  suara. Yang dijaga fitur ini adalah **aplikasi tidak bisa** mengubah suara, bukan
  manusia dengan kredensial database penuh.
- **Korelasi waktu (batas kerahasiaan yang nyata).** Tabel `suara` sendiri benar-benar
  anonim: tidak ada `id_siswa`, dan `id_suara` adalah UUID v4 acak. Namun panitia yang
  membuka monitor saat pemungutan berlangsung bisa **menyimpulkan** pilihan seseorang
  dengan mencocokkan tiga hal: kenaikan hitungan suara per kandidat, jam `waktu_memilih`
  di halaman Pemilih, dan baris log `PEMILIH_MEMILIH`. Pada pemilihan sekelas dengan
  sedikit pemilih, korelasi ini bisa menunjuk satu orang. Untuk pemilihan yang benar-benar
  rahasia, tutup monitor selama pemungutan berlangsung, atau jangan tampilkan jam memilih
  ke panitia.
- **Identitas pemanggil procedure.** `sp_rekam_suara` menerima `p_id_siswa` sebagai
  parameter, jadi siapa pun yang memegang kredensial `siswa_app` (default
  `siswa_app`/`siswa_app`) bisa merekam suara atas nama siswa lain. Lewat aplikasi hal ini
  tidak mungkin, karena controller selalu mengirim `Auth::user()->siswa->id_siswa`.
- Panitia memegang `INSERT/UPDATE/DELETE` pada tabel pemilihan karena perlu mengelola data
  saat `draf`. Begitu pemilihan dibuka, trigger menolak penambahan/penubahan kandidat dan
  pemilih, menolak pengembalian `sudah_memilih` ke 0, menolak penghapusan pemilihan yang
  sudah bersuara, dan hanya mengizinkan transisi status
  `draf → berlangsung`, `berlangsung → selesai`, `berlangsung → draf`.
- **Membuat pemilihan baru hanya boleh admin** (lapisan aplikasi). `sp_simpan_pemilihan`
  sendiri masih menerima admin *atau* panitia, jadi batasan ini ditegakkan oleh
  `PemilihanPolicy::buat()` di `KelolaPemilihanController::form()`/`simpan()` pada cabang
  pembuatan. Panitia tetap bisa menyunting pemilihan yang sudah ditugaskan.
- Waktu selesai terlewat **tidak** menutup pemilihan otomatis. Panitia harus menekan
  Tutup; halaman monitor menampilkan peringatan. Pemungutan suara sendiri sudah berhenti
  karena `sp_rekam_suara` memeriksa rentang waktu.
- Batas waktu memakai rentang **setengah terbuka** `[waktu_mulai, waktu_selesai)`:
  suara pada detik `waktu_selesai` sudah ditolak.

## 7. Menjalankan test

```bash
php artisan test
```

Test memakai database **terpisah** `msbd_25_test` (lihat `phpunit.xml`), bukan `msbd_25`.
Sekali saja, siapkan:

```bash
php -r "new PDO('mysql:host=127.0.0.1', 'root', '');" # pastikan MySQL hidup
# buat database uji bila belum ada:
php artisan tinker --execute="DB::statement('CREATE DATABASE IF NOT EXISTS msbd_25_test')"
DB_DATABASE=msbd_25_test php artisan migrate
```

Setiap kelas test memakai `DatabaseTruncation` karena middleware `DBDynamicConnection`
mengganti koneksi default sesuai role, sehingga transaksi test tidak akan terlihat oleh
koneksi `mysql_siswa`/`mysql_admin`.
