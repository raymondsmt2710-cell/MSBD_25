# View & Query Kompleks

## Pengertian Query Kompleks

Query kompleks adalah perintah SQL yang menggabungkan beberapa operasi
untuk mendapatkan informasi yang lebih detail dan bermakna. Jika query
sederhana hanya mengambil data dari satu tabel dengan kondisi tertentu,
maka query kompleks bisa melibatkan:

-   JOIN antar-tabel
-   Subquery (query di dalam query)
-   Aggregate function (COUNT, SUM, AVG, dll.)
-   GROUP BY dan HAVING untuk pengelompokan data

Dengan query kompleks, kita bisa menjawab pertanyaan riil dan juga
detail pada sistem perpustakaan, seperti:

-   "Siapa saja anggota yang paling sering meminjam buku?"
-   "Buku apa saja yang saat ini statusnya masih dipinjam?"
-   "Buku mana yang paling populer atau paling banyak dipinjam?"

**Studi Kasus: Database Perpustakaan Kemaren Je**

## Contoh Query Kompleks

### 1. JOIN (Menggabungkan Beberapa Tabel)

JOIN dipakai ketika kita ingin mengambil informasi lengkap yang tersebar
di beberapa tabel terpisah.

**Contoh masalah:**

"Tampilkan detail transaksi peminjaman lengkap dengan nama anggota,
judul buku, dan status pengembaliannya."

**SQL**

``` sql
SELECT
    p.id_peminjaman,
    a.nama_anggota,
    b.judul_buku,
    p.tanggal_pinjam,
    p.batas_kembali,
    dp.status_kembali
FROM peminjaman p
JOIN anggota a ON p.id_anggota = a.id_anggota
JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
JOIN buku b ON dp.id_buku = b.id_buku;
```

**Penjelasan:**

-   peminjaman menyimpan informasi tanggal transaksi.
-   anggota menyimpan siapa yang meminjam.
-   detail_peminjaman menyimpan status buku.
-   buku menyimpan judul buku yang dipinjam.

### 2. Aggregate Function + GROUP BY

Fungsi agregasi (COUNT) dikombinasikan dengan GROUP BY untuk menghitung
total akumulasi data.

**Contoh masalah:**

"Hitung total berapa banyak buku yang pernah dipinjam oleh masing-masing
anggota."

**SQL**

``` sql
SELECT
    a.nama_anggota,
    a.program_studi,
    COUNT(dp.id_detail) AS total_buku_dipinjam
FROM anggota a
JOIN peminjaman p ON a.id_anggota = p.id_anggota
JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
GROUP BY a.id_anggota, a.nama_anggota, a.program_studi;
```

**Penjelasan:**

-   `COUNT(dp.id_detail)` → menghitung jumlah baris peminjaman buku.
-   `GROUP BY a.id_anggota` → mengelompokkan hitungan berdasarkan
    anggota.

### 3. HAVING (Filter Setelah Agregasi)

WHERE tidak bisa digunakan untuk memfilter hasil fungsi agregat,
sehingga kita harus menggunakan HAVING.

**Contoh masalah:**

"Tampilkan anggota yang total peminjaman bukunya lebih dari 1 kali."

**SQL**

``` sql
SELECT
    a.nama_anggota,
    COUNT(dp.id_detail) AS total_buku_dipinjam
FROM anggota a
JOIN peminjaman p ON a.id_anggota = p.id_anggota
JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
GROUP BY a.id_anggota, a.nama_anggota
HAVING total_buku_dipinjam > 1;
```

### 4. Subquery (Query di dalam Query)

Subquery berguna untuk mencari data berdasarkan hasil perhitungan dari
query lain.

**Contoh masalah:**

"Buku apa yang paling sering dipinjam di perpustakaan?"

**SQL**

``` sql
SELECT
    judul_buku,
    kategori,
    (SELECT COUNT(id_buku)
     FROM detail_peminjaman
     WHERE id_buku = buku.id_buku) AS total_dipinjam
FROM buku
ORDER BY total_dipinjam DESC
LIMIT 1;
```

**Penjelasan:**

-   Query di dalam kurung (SELECT ...) mencari id_buku dengan peminjaman
    terbanyak.
-   Query utama mengambil judul_buku berdasarkan id_buku hasil subquery
    tersebut.

## VIEW (Tabel Virtual)

### Apa itu VIEW?

-   VIEW adalah tabel virtual yang berisi hasil dari query SQL.
-   Disimpan di database sebagai objek query, tetapi tidak menyimpan
    data fisik baru.
-   Digunakan untuk menyederhanakan query JOIN yang panjang agar mudah
    dipanggil kembali.
-   VIEW bisa dianggap sebagai "shortcut" query.

### Syntax VIEW

#### 1. Membuat VIEW

**SQL**

``` sql
CREATE VIEW [nama_view] AS
[query_sql];
```

Membuat view baru berdasarkan query SQL.

Jika nama view sudah ada, perintah akan error.

#### 2. Mengubah VIEW

**SQL**

``` sql
ALTER VIEW [nama_view] AS
[query_sql];
```

Mengganti definisi query pada view yang sudah ada.

#### 3. Menghapus VIEW

**SQL**

``` sql
DROP VIEW [nama_view];
```

Menghapus view dari database tanpa menghapus data tabel asli.

Jika ingin lebih aman dari error:

**SQL**

``` sql
DROP VIEW IF EXISTS [nama_view];
```

#### 4. Membuat atau Mengganti (Replace) VIEW

**SQL**

``` sql
CREATE OR REPLACE VIEW [nama_view] AS
[query_sql];
```

-   Jika view belum ada → dibuat baru.
-   Jika view sudah ada → definisinya langsung diperbarui.

#### 5. Melihat Daftar VIEW

**SQL**

``` sql
SHOW FULL TABLES WHERE table_type = 'VIEW';
```

#### 6. Melihat Definisi Query VIEW

**SQL**

``` sql
SHOW CREATE VIEW [nama_view];
```

## Contoh Kasus VIEW pada Database Perpustakaan

### Membuat VIEW Transaksi Peminjaman Buku yang Masih Dipinjam

**SQL**

``` sql
CREATE VIEW v_buku_sedang_dipinjam AS
SELECT
    p.id_peminjaman,
    a.nama_anggota,
    a.program_studi,
    b.judul_buku,
    p.tanggal_pinjam,
    p.batas_kembali
FROM peminjaman p
JOIN anggota a ON p.id_anggota = a.id_anggota
JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
JOIN buku b ON dp.id_buku = b.id_buku
WHERE dp.status_kembali = 'Dipinjam';
```

### Cara Pemanggilan VIEW

Setelah VIEW dibuat, kita cukup menjalankan query sederhana tanpa perlu
menulis ulang seluruh sintaks JOIN:

**SQL**

``` sql
SELECT * FROM v_buku_sedang_dipinjam;
```

## Kesimpulan

Gunakan Query Kompleks (JOIN, GROUP BY, Subquery) untuk mengolah dan
menganalisis data relasional, lalu simpan query tersebut ke dalam VIEW
agar dapat dipanggil kembali dengan mudah tanpa menulis ulang query yang
panjang.
