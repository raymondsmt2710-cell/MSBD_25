# Optimasi Query & Indexing

Seiring bertambahnya data di database perpustakaan (misalnya mencapai jutaan data peminjaman), query pencarian bisa menjadi sangat lambat. Di sinilah **Indexing** dan **Optimasi Query** berperan penting untuk menjaga performa database agar tetap cepat dan efisien.

## Indexing

Indexing pada database adalah proses membuat indeks pada satu atau beberapa kolom tabel agar MySQL dapat menemukan data dengan lebih cepat tanpa harus memeriksa seluruh baris data satu per satu.

Bayangkan index seperti "Daftar Isi" pada buku. Tanpa daftar isi, kamu harus mencari halaman demi halaman untuk menemukan bab tertentu (*Full Table Scan*). Dengan daftar isi, kamu bisa langsung menuju ke halaman yang tepat.

## Urutan Alur Kerja Query Database

![Urutan Alur Kerja Query Database](Gemini_Generated_Image_2mg8jr2mg8jr2mg8.jpg)

### Sintaks Dasar Membuat Index

```sql
CREATE INDEX nama_index ON nama_tabel (nama_kolom);
```

**Contoh di Database Perpustakaan:**

```sql
-- Membuat index pada kolom tanggal_pinjam di tabel peminjaman
CREATE INDEX idx_tanggal_pinjam ON peminjaman (tanggal_pinjam);

-- Membuat index pada kolom nama_anggota di tabel anggota
CREATE INDEX idx_nama_anggota ON anggota (nama_anggota);

-- Membuat index pada kolom judul_buku di tabel buku
CREATE INDEX idx_judul_buku ON buku (judul_buku);
```

**Menghapus Index:**

```sql
-- Menghapus index pada kolom nama_anggota di tabel anggota
DROP INDEX idx_nama_anggota ON anggota;
```

### 📌 Kapan Harus Menggunakan Index?

1. Pada kolom yang sering digunakan dalam klausa `WHERE`.
2. Pada kolom yang sering digunakan untuk menggabungkan tabel (`JOIN`).
3. Pada kolom yang sering digunakan untuk mengurutkan data (`ORDER BY`).

### ⚠️ Kapan TIDAK Disarankan Menggunakan Index?

- Tabel dengan jumlah baris yang sedikit.
- Tabel yang sangat sering mengalami proses `INSERT`, `UPDATE`, atau `DELETE` (karena setiap kali data diubah, index juga harus diperbarui, yang memakan waktu).

## ⚡ Optimasi Query

Optimasi query adalah seni menulis perintah SQL agar database tidak bekerja terlalu berat dan mengembalikan hasil secepat mungkin.

### Praktik Terbaik (Best Practices) Optimasi Query

**1. Hindari Penggunaan `SELECT *`**

Mengambil semua kolom akan membebani memori dan jaringan. Ambil kolom yang benar-benar dibutuhkan saja.

❌ **Buruk:**

```sql
SELECT * FROM anggota;
```

✅ **Baik:**

```sql
SELECT id_anggota, nama_anggota FROM anggota;
```

**2. Gunakan Klausa `EXPLAIN`**

Tambahkan kata `EXPLAIN` di depan query untuk melihat bagaimana MySQL mengeksekusi query tersebut. Ini sangat berguna untuk mengecek apakah query sudah menggunakan index atau belum.

```sql
EXPLAIN SELECT * FROM anggota WHERE nama_anggota = 'Mahasiswa_Dummy_4500';
```

**3. Hindari Fungsi di dalam Klausa `WHERE` pada Kolom Ber-Index**

Jika kamu memanipulasi kolom dengan fungsi, MySQL tidak bisa menggunakan index dan akan memindai seluruh tabel.

❌ **Buruk (Index tidak terpakai):**

```sql
SELECT * FROM peminjaman WHERE DATE(tanggal_pinjam) = '2026-02-07';
```

✅ **Baik (Index terpakai):**

```sql
SELECT * FROM peminjaman WHERE tanggal_pinjam = '2026-02-07';
```

**4. Batasi Hasil dengan `LIMIT`**

Jika kamu memiliki 10.000 buku tapi hanya ingin menampilkan di halaman pertama, selalu gunakan paginasi.

```sql
SELECT judul_buku, stok FROM buku ORDER BY kategori DESC LIMIT 10;
```

**5. Optimalkan `JOIN`**

Sebagai penjelasan, berikut adalah perbandingan query untuk menampilkan **"Daftar buku yang sedang dipinjam (belum dikembalikan) beserta nama peminjamnya"**.

❌ **Query Kurang Optimal** *(Mengambil semua data yang tidak perlu)*:

```sql
SELECT *
FROM anggota a, peminjaman p, detail_peminjaman dp, buku b
WHERE a.id_anggota = p.id_anggota
  AND p.id_peminjaman = dp.id_peminjaman
  AND dp.id_buku = b.id_buku
  AND dp.status_kembali = 'Dipinjam';
```

✅ **Query Optimal** *(Menggunakan standar INNER JOIN dan mengambil kolom seperlunya)*:

```sql
SELECT
    a.nama_anggota,
    b.judul_buku,
    p.tanggal_pinjam,
    p.batas_kembali
FROM anggota a
INNER JOIN peminjaman p ON a.id_anggota = p.id_anggota
INNER JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
INNER JOIN buku b ON dp.id_buku = b.id_buku
WHERE dp.status_kembali = 'Dipinjam';
```

**💡 Mengapa lebih optimal?**

1. Kita membatasi kolom yang diambil (`SELECT a.nama_anggota, b.judul_buku...`) daripada menggunakan `SELECT *`.
2. Struktur `INNER JOIN` lebih mudah dibaca dan dieksekusi secara efisien oleh mesin MySQL (berkat *Index* pada *Foreign Key* yang sudah ada di database).

### 🎯 Kesimpulan

- **Indexing & Optimasi Query** berfungsi memastikan sistem informasi perpustakaan berjalan cepat, responsif, dan tidak memakan terlalu banyak resource server, meskipun jumlah buku dan anggota sudah mencapai puluhan ribu.
