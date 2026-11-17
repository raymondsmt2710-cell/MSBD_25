# Rancangan Fitur: Pemilihan Suara Elektronik (e-Voting)

Tanggal: 2026-11-17
Status: menunggu persetujuan
Terkait: `AGENTS.md` fitur #3 "Pemilihan suara elektronik", `MSBD_25/*.md`

---

## 1. Maksud dan Alasan

Sekolah membutuhkan pemilihan pengurus organisasi (mis. OSIS) yang:

- **jujur** — satu siswa hanya bisa memberikan satu suara, dan tidak bisa diakali walaupun
  aplikasi punya bug;
- **rahasia** — tidak ada satu pun kolom di database yang bisa menghubungkan identitas
  siswa dengan kandidat yang ia pilih;
- **tercatat** — panitia tahu siapa saja yang sudah memilih tanpa tahu pilihannya, dan
  setiap perubahan status pemilihan meninggalkan jejak audit;
- **dapat dipertanggungjawabkan** — hasil dihitung dari data, bukan dari rekap manual.

Fitur ini juga menjadi tempat penerapan objek database MySQL yang dipelajari di praktikum
(Stored Procedure, Stored Function, View, Trigger, transaksi, GRANT, index), mengikuti pola
yang sudah dipakai fitur presensi.

## 2. Sasaran

1. Admin dan panitia dapat membuat pemilihan, mengelola kandidat, mengelola daftar pemilih,
   dan membuka/menutup pemilihan.
2. Siswa yang terdaftar sebagai pemilih dapat memilih tepat satu kali melalui halaman web.
3. Perolehan suara dapat dilihat setelah pemilihan ditutup.
4. Semua operasi tulis yang menyentuh suara pemilih dijaga oleh database.

## 3. Bukan Sasaran

- Pemilihan berbasis QR/kehadiran fisik di TPS (fitur presensi sudah punya mekanismenya sendiri).
- Kandidat mendaftarkan diri sendiri (pendaftaran tetap oleh admin/panitia).
- Voting berbobot (mis. suara guru berbeda bobotnya).
- Notifikasi email/WhatsApp.
- Aplikasi mobile terpisah; tetap web responsif.
- Perhitungan hasil dengan metode selain *plurality* (suara terbanyak).

## 4. Anti-Sasaran (kegagalan walau fitur "jalan")

- Siswa bisa memilih dua kali, atau memilih lewat dua tab/perangkat.
- Ada cara (kolom, log, atau urutan baris) untuk menyimpulkan pilihan seorang siswa.
- Admin/panitia bisa mengubah suara yang sudah masuk tanpa jejak.
- Hasil dihitung dari kode PHP yang membaca tabel langsung tanpa penjagaan database,
  sehingga satu bug kecil merusak keabsahan pemilu.
- Siswa melihat hitungan sementara saat pemilihan masih berlangsung dan terpengaruh.

## 5. Batasan

- Laravel 12, PHP 8.2+, MySQL 8.4 (InnoDB). Tanpa dependency Composer baru.
- Tabel `pemilihan`, `panitia_pemilihan`, `daftar_pemilih`, `kandidat`, `suara` **sudah ada**
  (migration `2026_11_16_000022`–`000026`) dan tidak diubah strukturnya. Kolom yang
  dibutuhkan sudah tersedia: `pemilihan.status` (`draf`/`berlangsung`/`selesai`),
  `pemilihan.waktu_mulai`/`waktu_selesai`, `daftar_pemilih.sudah_memilih`/`waktu_memilih`,
  `kandidat.foto_url`, `suara.id_suara` (UUID) tanpa `id_siswa`.
- Migration `2026_11_16_000027`–`000033` masih pending dan akan ikut berjalan saat
  `php artisan migrate`. Tidak ada `migrate:fresh`, jadi data yang ada tidak dihapus.
- `.env` tidak disentuh. Password user MySQL memakai default di dalam kode
  (`admin_app`, `guru_app`, `siswa_app`, `ortu_app`).
- Bahasa antarmuka: Indonesia. Front end mengikuti gaya <https://sman1kabanjahe.sch.id/>:
  warna utama teal `#09656F`, aksen oranye `#C97A14`, kartu `rounded-2xl`, font Figtree,
  tombol membulat, tanpa framework CSS baru (Tailwind sudah terpasang).
- Tidak `git push` / `git pull`.

## 6. Pendekatan yang Dipertimbangkan

### Pendekatan A — Stored Procedure sebagai satu-satunya pintu masuk suara **(dipilih)**

Semua aturan pemungutan berada di `sp_rekam_suara`. Controller hanya memanggil
`DB::statement('CALL ...', [...])` di dalam `DB::beginTransaction()`.

- **Kelebihan:** konsisten dengan `sp_scan_presensi` yang sudah ada; siswa **tidak** diberi
  hak `INSERT` ke `suara` sama sekali (hanya `EXECUTE`), sehingga suara ganda mustahil
  walaupun aplikasi salah; anonimitas dijaga di level database; satu tempat untuk seluruh
  aturan.
- **Kekurangan:** logika bisnis "tersembunyi" di database, perlu dibaca lewat migration.

### Pendekatan B — Logika di Controller tanpa Stored Procedure

Validasi ditulis di PHP dengan query builder + `DB::transaction()`.

- **Kelebihan:** mudah dibaca dan di-debug dari satu bahasa.
- **Kekurangan:** tidak memakai materi Stored Procedure; siswa butuh hak `INSERT` ke
  `suara`, sehingga tidak ada lapis pengaman database; menyimpang dari pola presensi.

### Pendekatan C — Aturan dipecah antara Controller dan Trigger

Controller memeriksa status/waktu, Trigger `BEFORE INSERT` memeriksa sisanya.

- **Kelebihan:** tampak rapi dan "database-centric".
- **Kekurangan:** satu aturan tersebar di dua tempat, mudah berbeda diam-diam saat diubah;
  pesan error dari trigger lebih sulit diterjemahkan ke UI.

**Keputusan:** Pendekatan A, dipilih oleh pengguna setelah ketiga opsi diuraikan.

## 7. Keputusan yang Sudah Diambil Pengguna

| Hal | Keputusan |
| --- | --- |
| Siapa yang mengelola pemilihan | **Admin + panitia.** Admin membuat pemilihan dan menunjuk panitia lewat `panitia_pemilihan`; panitia mengelola pemilihan yang ditugaskan kepadanya. |
| Kapan hasil terlihat | **Setelah status `selesai`, semua boleh melihat.** Saat `berlangsung`, siswa hanya melihat status "sudah memilih" dan persentase partisipasi; admin/panitia selalu bisa memantau hitungan sementara. |
| Cara mengisi daftar pemilih | **Pilih per kelas.** Admin/panitia memilih satu atau beberapa kelas; semua siswa berstatus `aktif` di kelas itu otomatis menjadi pemilih. |
| Foto kandidat | **Upload file gambar**, disimpan di disk publik, kolom `kandidat.foto_url` menyimpan path relatifnya. |
| Yang boleh diubah saat `berlangsung` | **Dikunci total.** Judul, waktu, kandidat, dan daftar pemilih tidak bisa diubah setelah pemilihan dibuka. Hanya tombol Buka/Tutup yang aktif. Untuk memperbaiki data, pemilihan harus dikembalikan ke `draf`, dan pengembalian itu tercatat di log. |
| Waktu selesai terlewat | **Tidak menutup otomatis.** Status tetap `berlangsung` sampai panitia menekan Tutup. Halaman kelola menampilkan peringatan "waktu sudah lewat — silakan tutup". Pemungutan suara tetap berhenti sendiri karena `sp_rekam_suara` memeriksa rentang waktu. |

## 8. Rancangan

### 8.1 Objek Database

**Stored Procedure**

Seluruh operasi tulis pada data pemilihan dilakukan lewat procedure, sehingga pemeriksaan
"hanya boleh saat `draf`" dan "hanya admin/panitia" berada di satu tempat dan tidak
bergantung pada kerapian kode PHP. Semua procedure kelola menerima `p_id_user` dan
memeriksa sendiri apakah user tersebut admin atau panitia pemilihan itu; jika tidak,
kembalikan `TIDAK_BERHAK`.

| Nama | Parameter | Tugas |
| --- | --- | --- |
| `sp_rekam_suara` | `IN p_id_pemilihan INT, IN p_id_siswa INT, IN p_id_kandidat INT, OUT p_status VARCHAR(40)` | Satu-satunya jalan masuk suara. Status: `BERHASIL`, `PEMILIHAN_TIDAK_VALID`, `BELUM_DIMULAI`, `SUDAH_DITUTUP`, `BUKAN_PEMILIH`, `SUDAH_MEMILIH`, `KANDIDAT_TIDAK_VALID`. |
| `sp_buka_pemilihan` | `IN p_id_pemilihan INT, IN p_id_user INT, OUT p_status VARCHAR(30)` | `draf` → `berlangsung`. Menolak bila `waktu_selesai` sudah lewat atau belum ada kandidat/pemilih. |
| `sp_tutup_pemilihan` | `IN p_id_pemilihan INT, IN p_id_user INT, OUT p_status VARCHAR(30), OUT p_jumlah_suara INT` | `berlangsung` → `selesai`. Mengembalikan jumlah suara masuk. |
| `sp_kembalikan_ke_draf` | `IN p_id_pemilihan INT, IN p_id_user INT, OUT p_status VARCHAR(30)` | `berlangsung` → `draf` untuk perbaikan data. **Ditolak bila sudah ada suara masuk** (`fn_total_suara > 0`), karena membuka kembali pemilihan setelah ada suara berarti mengubah hasil. |
| `sp_simpan_pemilihan` | `IN p_id_pemilihan INT` (NULL = baru), `IN p_id_user INT, IN p_judul, IN p_deskripsi, IN p_id_organisasi, IN p_id_tahun_ajaran, IN p_waktu_mulai, IN p_waktu_selesai, OUT p_status VARCHAR(30), OUT p_id_hasil INT` | INSERT bila baru, UPDATE bila ada. Menolak bila status bukan `draf`. |
| `sp_simpan_kandidat` | `IN p_id_kandidat INT` (NULL = baru), `IN p_id_user INT, IN p_id_pemilihan, IN p_nomor_urut, IN p_id_siswa_ketua, IN p_id_siswa_wakil, IN p_visi, IN p_misi, IN p_foto_url, OUT p_status VARCHAR(30), OUT p_id_hasil INT` | INSERT/UPDATE kandidat. Menolak bila status pemilihan bukan `draf`. |
| `sp_hapus_kandidat` | `IN p_id_kandidat INT, IN p_id_user INT, OUT p_status VARCHAR(30)` | Menghapus kandidat. Menolak bila bukan `draf` atau bila kandidat sudah punya suara. |
| `sp_daftarkan_pemilih_kelas` | `IN p_id_pemilihan INT, IN p_id_user INT, IN p_id_kelas INT, OUT p_status VARCHAR(30), OUT p_jumlah INT` | Mendaftarkan semua siswa `aktif` di kelas tersebut. `INSERT IGNORE` sehingga aman diulang. Menolak bila bukan `draf`. |
| `sp_hapus_pemilih` | `IN p_id_pemilihan INT, IN p_id_user INT, IN p_id_siswa INT, OUT p_status VARCHAR(30)` | Menghapus satu pemilih. Menolak bila bukan `draf` atau bila siswa sudah memilih. |
| `sp_tunjuk_panitia` | `IN p_id_pemilihan INT, IN p_id_user INT, IN p_id_user_panitia INT, OUT p_status VARCHAR(30)` | Menambah/menghapus panitia. **Hanya admin** (bukan panitia). Menolak bila bukan `draf`. |

**Stored Function** (hanya membaca)

| Nama | Kembalian | Tugas |
| --- | --- | --- |
| `fn_sudah_memilih` | `TINYINT(1)` | 1 bila siswa terdaftar dan sudah memilih pada pemilihan tsb. |
| `fn_boleh_memilih` | `TINYINT(1)` | 1 bila pemilihan `berlangsung`, waktu sekarang dalam rentang, dan siswa terdaftar serta belum memilih. Dipakai untuk menampilkan tombol pilih. |
| `fn_total_suara` | `INT` | Jumlah suara sah pada satu pemilihan. |
| `fn_partisipasi` | `DECIMAL(5,2)` | Persentase `sudah_memilih` terhadap total pemilih. |

**View**

Perhatikan pembagian berikut, karena `DBDynamicConnection` membuat controller membaca
database **sebagai user MySQL milik role yang login**:

- **`v_hasil_pemilihan` menyembunyikan angkanya sendiri.** Kolom `jumlah_suara` dan
  `persentase` dibungkus `CASE WHEN p.status = 'selesai' THEN ... ELSE NULL END`. Siswa
  boleh membaca view ini, tetapi selama pemilihan belum `selesai` ia hanya menerima `NULL`.
  Dengan begitu aturan "hasil hanya setelah selesai" berlaku di database, bukan hanya di
  controller, dan halaman siswa tetap bisa membaca view itu tanpa error hak akses.
- **`v_kandidat_pemilihan_hasil` tidak disembunyikan** dan hanya diberikan kepada
  guru/admin, untuk monitor hitungan sementara oleh panitia.

| Nama | Isi | Boleh dibaca |
| --- | --- | --- |
| `v_kandidat_pemilihan` | Kandidat + nama ketua + nama wakil + visi/misi/foto. **Tanpa kolom jumlah suara.** | siswa, guru, admin |
| `v_kandidat_pemilihan_hasil` | Sama seperti di atas **plus** `jumlah_suara` dan `persentase`. Hitungan selalu tampil. | guru, admin |
| `v_hasil_pemilihan` | Rekap per kandidat: nomor urut, nama, `jumlah_suara`, `persentase`, penanda pemenang. Hitungan **`NULL` bila status bukan `selesai`**. | siswa, guru, admin |
| `v_partisipasi_pemilihan` | Per pemilihan: total pemilih, sudah memilih, persentase. Tidak menyingkap pilihan siapa pun. | siswa, guru, admin |
| `v_pemilihan_pengelola` | Pemilihan + organisasi + tahun ajaran + nama pembuat, untuk daftar kelola. | guru, admin |

**Trigger**

| Nama | Waktu | Tugas |
| --- | --- | --- |
| `trg_before_suara_insert` | BEFORE INSERT ON `suara` | Menolak (`SIGNAL SQLSTATE '45000'`) bila `id_pemilihan` bukan `berlangsung` atau kandidat bukan milik pemilihan tersebut. Penjaga terakhir di level database. |
| `trg_before_suara_update` | BEFORE UPDATE ON `suara` | Menolak setiap perubahan suara. Suara bersifat *immutable*. |
| `trg_before_suara_delete` | BEFORE DELETE ON `suara` | Menolak penghapusan suara. Satu-satunya cara "membatalkan" suara adalah pemilihan baru. |
| `trg_before_kandidat_delete` | BEFORE DELETE ON `kandidat` | Menolak penghapusan kandidat yang sudah memiliki suara. Tanpa ini, `cascadeOnDelete` pada `suara.id_kandidat` akan menghapus suara diam-diam dan mengubah hasil. |
| `trg_before_daftar_pemilih_delete` | BEFORE DELETE ON `daftar_pemilih` | Menolak penghapusan pemilih yang sudah memilih, agar `total suara` tidak pernah melebihi `jumlah sudah_memilih`. |
| `trg_before_pemilihan_update` | BEFORE UPDATE ON `pemilihan` | Menolak perubahan `waktu_mulai`, `waktu_selesai`, `judul`, `id_organisasi`, `id_tahun_ajaran` bila status saat ini bukan `draf`. Ini yang menegakkan keputusan "kunci total setelah dibuka" walaupun ada kode PHP yang lupa memeriksa. |
| `trg_after_pemilihan_update` | AFTER UPDATE ON `pemilihan` | Mencatat perubahan `status` ke `log_pemilihan` (aksi, status lama, status baru, oleh siapa, waktu). |
| `trg_after_daftar_pemilih_update` | AFTER UPDATE ON `daftar_pemilih` | Mencatat waktu memilih ke `log_pemilihan` **tanpa** menyebut kandidat, sehingga keikutsertaan tetap dapat diaudit tanpa membocorkan pilihan. |

**Soal "oleh siapa" di dalam trigger.** Trigger tidak punya akses ke user aplikasi. Karena
itu, setiap procedure yang mengubah status pemilihan melakukan
`SET @id_user_app = p_id_user;` tepat sebelum `UPDATE pemilihan`, dan trigger membaca
variabel sesi itu (`COALESCE(@id_user_app, NULL)`). Bila ada penulis lain yang mengubah
status tanpa lewat procedure, `id_user` tercatat `NULL` — dan itu sendiri informasi yang
berguna: ada perubahan di luar jalur resmi.

**Anonimitas `suara.id_suara`.** Nilai UUID **tidak** dibuat dengan `UUID()` milik MySQL,
karena itu UUID versi 1 yang menyimpan stempel waktu 100-nanodetik. Karena `id_suara`
adalah primary key, urutan baris akan mengikuti urutan masuk, sehingga panitia bisa
mengurutkan suara berdasarkan waktunya lalu mencocokkannya dengan `waktu_memilih` di
`daftar_pemilih` dan merekonstruksi pilihan tiap siswa. Sebagai gantinya, procedure membuat
UUID versi 4 acak murni:

```sql
SET v_uuid = LOWER(CONCAT(
    SUBSTR(HEX(RANDOM_BYTES(4)),1,8), '-',
    SUBSTR(HEX(RANDOM_BYTES(2)),1,4), '-4',
    SUBSTR(HEX(RANDOM_BYTES(2)),1,3), '-',
    SUBSTR('89ab', FLOOR(1 + RAND()*4), 1),
    SUBSTR(HEX(RANDOM_BYTES(2)),1,3), '-',
    SUBSTR(HEX(RANDOM_BYTES(6)),1,12)
));
```

Konsekuensi lain yang harus dijaga: tidak ada kolom `created_at` di `suara` (memang tidak
ada), dan trigger log untuk `daftar_pemilih` tidak boleh menulis `id_kandidat`.

**Tabel baru**

`log_pemilihan(id_log, id_pemilihan, id_user NULL, aksi, keterangan, dibuat_pada)` — dibuat
oleh migration baru, hanya `INSERT` (lewat trigger) dan `SELECT`. Tidak menyimpan
`id_kandidat`.

**Index** — kolom yang benar-benar dipakai `WHERE`/`JOIN`:

- `daftar_pemilih (id_pemilihan, sudah_memilih)` — hitung partisipasi.
- `suara (id_pemilihan, id_kandidat)` — hitung perolehan.
- `kandidat (id_pemilihan)` — daftar kandidat (sudah tercakup unique `(id_pemilihan, nomor_urut)`; index terpisah ditambahkan hanya jika `EXPLAIN` menunjukkan kebutuhan).
- `pemilihan (status, waktu_mulai, waktu_selesai)` — daftar pemilihan aktif.

**GRANT** (migration baru, mengikuti pola `000033`)

`DBDynamicConnection` mengganti koneksi sesuai role, jadi tabel ini adalah lapis keamanan
yang sebenarnya. Karena `partial_revokes` di MySQL 8.4 Anda **OFF** (sudah saya verifikasi),
hak `GRANT ALL` tidak bisa dicabut sebagian per tabel. Karena itu migration voting:

1. memberi `admin_app` hak **per objek** (bukan `ALL ON db.*`) untuk tabel dan objek voting;
2. **tidak menyentuh** hak `admin_app` atas tabel presensi, agar fitur presensi yang sudah
   berjalan tidak berubah perilakunya.

| Objek | Admin | Panitia (role `guru`) | Siswa |
| --- | --- | --- | --- |
| `pemilihan`, `kandidat`, `daftar_pemilih`, `panitia_pemilihan` | SELECT, INSERT, UPDATE, DELETE | SELECT | SELECT |
| `suara` | SELECT | SELECT | **tidak ada** |
| `log_pemilihan` | SELECT | SELECT | SELECT |
| `v_kandidat_pemilihan`, `v_hasil_pemilihan`, `v_partisipasi_pemilihan` | SELECT | SELECT | SELECT |
| `v_kandidat_pemilihan_hasil`, `v_pemilihan_pengelola` | SELECT | SELECT | tidak ada |
| `sp_rekam_suara` | EXECUTE | EXECUTE | EXECUTE |
| Seluruh `sp_*` kelola pemilihan | EXECUTE | EXECUTE | tidak ada |
| `fn_sudah_memilih`, `fn_boleh_memilih`, `fn_partisipasi` | EXECUTE | EXECUTE | EXECUTE |
| `fn_total_suara` | EXECUTE | EXECUTE | tidak ada |

Catatan penting: siswa **tidak** mendapat `INSERT` ke `suara` maupun `UPDATE` ke
`daftar_pemilih`. Satu-satunya cara siswa menghasilkan suara adalah `EXECUTE sp_rekam_suara`.

**Batas yang perlu dinyatakan jujur.** Panitia ber-role `guru` memang memegang
`INSERT/UPDATE/DELETE` pada tabel voting, karena panitia perlu mengelola data saat `draf`.
Artinya panitia secara teknis bisa menulis ke tabel itu lewat jalur lain. Yang menutup celah
ini bukan GRANT, melainkan trigger `trg_before_*` di atas, yang menolak perubahan pada
`suara` dan menolak perubahan data pemilihan ketika status bukan `draf`. Dengan kata lain:
selama pemilihan `draf`, panitia adalah pihak yang dipercaya; begitu `berlangsung`, database
menolak semua perubahan data pemilihan dari siapa pun, termasuk admin.

Hak `admin_app` yang tersisa dari `000033` (`ALL ON db.*`) tetap ada dan tidak diubah.
Karena itu administrator database yang login langsung sebagai `admin_app` masih bisa
menghapus suara. Ini batas yang wajar untuk praktikum: yang dijaga adalah *aplikasi tidak
bisa* mengubah suara, bukan *manusia dengan kredensial root tidak bisa*. Batas ini dicatat
di dokumen agar tidak menimbulkan kesan aman berlebihan.

### 8.2 Alur `sp_rekam_suara`

```
1. Ambil status & rentang waktu pemilihan.
   Tidak ada                          → PEMILIHAN_TIDAK_VALID
2. Status = 'draf'                    → BELUM_DIMULAI
   Status = 'selesai'                 → SUDAH_DITUTUP
   NOW() < waktu_mulai                → BELUM_DIMULAI
   NOW() > waktu_selesai              → SUDAH_DITUTUP
3. Kandidat harus ada dan id_pemilihan-nya sama → KANDIDAT_TIDAK_VALID
4. Siswa harus ada di daftar_pemilih → BUKAN_PEMILIH
5. UPDATE daftar_pemilih
      SET sudah_memilih = 1, waktu_memilih = NOW()
      WHERE id_pemilihan = ? AND id_siswa = ? AND sudah_memilih = 0
   ROW_COUNT() = 0 → SUDAH_MEMILIH            ← kunci anti suara ganda
6. INSERT INTO suara (uuid_v4_acak, id_pemilihan, id_kandidat)   ← tanpa id_siswa
7. p_status = 'BERHASIL'
```

Controller membungkus langkah 1–7 dalam `DB::beginTransaction()` / `commit()` /
`rollBack()` sehingga langkah 5 dan 6 berhasil bersama atau batal bersama. Jika `ROLLBACK`,
`sudah_memilih` ikut kembali 0 sehingga siswa tidak "kehilangan" hak pilihnya karena error
server. `trg_before_suara_insert` menjadi penjaga terakhir: walaupun langkah 2 terlewat
karena bug, database tetap menolak suara pada pemilihan yang tidak `berlangsung`.

### 8.3 Hak Akses Aplikasi (lapis kedua)

Middleware `role` yang sudah ada dipakai di route. Panitia adalah user ber-role `guru` yang
terdaftar di `panitia_pemilihan` untuk pemilihan tersebut. Sebuah *policy* kecil
(`PemilihanPolicy`) memeriksa:

- `admin` → boleh semua;
- `guru` yang tercatat di `panitia_pemilihan` → boleh kelola pemilihan itu;
- selain itu → 403.

### 8.4 Halaman

**Siswa** — `/siswa/pemilihan`

| Halaman | Isi |
| --- | --- |
| Daftar pemilihan | Kartu pemilihan yang diikuti: judul, organisasi, status, rentang waktu, tombol "Pilih Sekarang" (aktif hanya bila `fn_boleh_memilih` = 1), atau label "Sudah memilih ✓", atau "Lihat Hasil" bila `selesai`. |
| Halaman pilih | Kartu kandidat (foto, nomor urut, nama ketua & wakil, visi ringkas, tombol "Lihat Detail"). Tombol "Pilih" membuka modal konfirmasi: "Suara Anda bersifat rahasia dan tidak dapat diubah. Pilih kandidat No. 02?" dengan tombol Batal / Ya, Kirim Suara. |
| Hasil | Hanya bila status `selesai`. Bar horizontal per kandidat + jumlah suara + persentase, penanda pemenang, dan ringkasan partisipasi. |

**Admin/Panitia** — `/kelola/pemilihan`

| Halaman | Isi |
| --- | --- |
| Daftar | Tabel pemilihan (judul, organisasi, tahun ajaran, status, waktu, jumlah pemilih, tombol Kelola). Tombol "Buat Pemilihan". |
| Form buat/ubah | Judul, deskripsi, organisasi, tahun ajaran, waktu mulai, waktu selesai. Saat dibuat status `draf`. Hanya bisa diubah saat `draf`. |
| Kandidat | Daftar kandidat + form tambah: nomor urut, siswa ketua (pencarian), siswa wakil (opsional), visi, misi, foto. Tombol tambah/ubah/hapus hilang saat status bukan `draf`. |
| Daftar pemilih | Pilih kelas (multi-select) → tombol "Daftarkan". Tabel pemilih: NIS, nama, kelas, sudah memilih (✓/–). Paginasi. Tombol hapus pemilih hanya saat `draf`. |
| Panitia | Admin saja: pilih guru untuk dijadikan panitia. Hanya saat `draf`. |
| Monitor | Hitungan sementara (hanya admin/panitia), partisipasi, tombol Buka / Tutup / Kembalikan ke Draf. Peringatan "waktu sudah lewat — silakan tutup" muncul bila `waktu_selesai` lewat tapi status masih `berlangsung`. |
| Log | Riwayat dari `log_pemilihan`. |

Semua tombol yang tidak tersedia disembunyikan **dan** tetap ditolak di database. Ini
disengaja: menyembunyikan tombol adalah kenyamanan, trigger dan procedure adalah penjaganya.

### 8.5 Komponen Teknis

- **Controller:** `SiswaPemilihanController` (daftar, pilih, simpan, hasil) dan
  `KelolaPemilihanController` (CRUD pemilihan, kandidat, pemilih, panitia, buka/tutup, monitor).
- **Model:** `Pemilihan`, `Kandidat`, `DaftarPemilih`, `Suara`, `PanitiaPemilihan`,
  `LogPemilihan`. Fungsi database dipanggil dari model, mengikuti pola `Presensi`/`Transaksi`.
- **Request:** `PemilihanRequest`, `KandidatRequest` untuk validasi.
- **Foto:** `Storage::disk('public')` di `storage/app/public/kandidat/`, disajikan lewat
  `php artisan storage:link`. Unggahan dibatasi `image|mimes:jpg,jpeg,png,webp|max:2048`.
- **Layout:** satu layout baru `layouts/pemilihan.blade.php` bergaya sekolah (header teal,
  kartu putih, aksen oranye) yang dipakai halaman siswa dan kelola. Layout `layouts.app`
  yang lama tidak diubah agar fitur presensi tetap seperti sekarang.
- **Anti-CSRF & anti-dobel-klik:** form `@csrf`, tombol dinonaktifkan setelah diklik,
  dan `sp_rekam_suara` tetap menjadi penjaga terakhir.
- **Data contoh:** seeder baru `PemilihanSeeder` (dipanggil dari `DatabaseSeeder`) membuat
  1 tahun ajaran, 1 organisasi, 3 kelas, 9 siswa (dengan akun `siswa1`–`siswa9`, password
  `password`), 1 pemilihan berstatus `berlangsung`, 3 kandidat, dan panitia dari guru yang
  ada. Seeder bersifat idempoten (menggunakan `firstOrCreate`/cek keberadaan).

## 9. Kriteria Selesai

Fitur dianggap selesai bila seluruh poin berikut terbukti, bukan hanya "halaman terbuka":

1. Siswa terdaftar bisa memilih sekali; percobaan kedua menghasilkan pesan "Anda sudah
   memilih" dan jumlah suara tidak bertambah.
2. Menekan tombol pilih dua kali cepat (dobel klik) tetap menghasilkan satu suara.
3. Siswa yang tidak terdaftar tidak bisa memilih.
4. Memilih saat status `draf` atau `selesai` ditolak, begitu juga di luar rentang waktu.
5. Siswa tidak bisa membaca hitungan selama status `berlangsung` — dicek langsung dengan
   query `SELECT jumlah_suara FROM v_hasil_pemilihan` memakai koneksi `siswa_app`, hasilnya
   `NULL`.
6. `UPDATE` dan `DELETE` pada `suara` ditolak database.
7. Menghapus kandidat yang sudah punya suara ditolak; menghapus pemilih yang sudah memilih
   ditolak.
8. Mengubah waktu atau judul pemilihan saat `berlangsung` ditolak database.
9. `EXPLAIN` pada query hitung suara dan partisipasi memakai index yang dibuat.
10. Halaman siswa, kelola, dan hasil tampil rapi di lebar ponsel maupun desktop.

## 10. Yang Diserahkan ke Pelaksana

- Nama kelas, organisasi, dan kandidat pada data contoh.
- Susunan detail markup/CSS selama mengikuti palet dan gaya yang disebut di Batasan.
- Apakah halaman detail kandidat berupa modal atau halaman terpisah (keduanya diterima;
  halaman terpisah lebih ramah untuk teks visi/misi panjang).
- Teks pesan galat untuk tiap `p_status`.
- Jumlah baris per halaman pada daftar pemilih (usulan: 25).
- Apakah tombol "Kembalikan ke Draf" ditampilkan di monitor atau disembunyikan di menu
  terpisah, selama syarat "tidak boleh ada suara" tetap ditegakkan database.
