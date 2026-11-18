# Stored Function

## 1. Pengertian Stored Function

**Function** adalah sekumpulan perintah SQL yang dirancang khusus untuk melakukan perhitungan dan **wajib** mengembalikan satu nilai (`RETURN`). Function biasanya dipanggil di dalam query SQL (seperti di dalam `SELECT` atau `WHERE`) dan umumnya tidak boleh memodifikasi data (tidak boleh ada `INSERT`, `UPDATE`, `DELETE`).

### Tujuan Utama Penggunaan Stored Function

1. **Menghindari Penulisan Ulang Logika:** Logika perhitungan (misal: hitung denda, hitung diskon) cukup dibuat satu kali dan dapat dipakai di banyak *query* `SELECT`.
2. **Menyembunyikan Kompleksitas:** Kita cukup memanggil nama fungsi tanpa perlu tahu rumus rumit di dalamnya.
3. **Meningkatkan Readability (Keterbacaan) Kode:** Query SQL yang panjang menjadi lebih ringkas dan mudah dibaca.

## 2. Perbedaan Utama: Function vs Procedure

Agar lebih mudah dipahami, berikut adalah perbandingan antara keduanya:

| Fitur | Stored Function | Stored Procedure |
| --- | --- | --- |
| **Tujuan Utama** | Mengembalikan **sebuah nilai** (kalkulasi/transformasi data). | Melakukan **rangkaian operasi bisnis** yang kompleks. |
| **Cara Pemanggilan** | Dipanggil di dalam ekspresi SQL: `SELECT nama_fungsi(A, B)` | Dipanggil dengan perintah khusus: `CALL nama_procedure(A, B)` |
| **Parameter** | Hanya menerima parameter input (**IN**). | Bisa menerima **IN**, **OUT**, dan **INOUT**. |
| **Side-Effects (Mengubah Data)** | Idealnya **HANYA MEMBACA** data. Tidak boleh ada `INSERT`/`UPDATE`/`DELETE` agar tidak merusak hasil `SELECT`. | Sangat umum digunakan untuk **MENGUBAH** data (`INSERT`/`UPDATE`/`DELETE`). |

## 3. Sintaks Dasar Stored Function

### A. Membuat Stored Function

```sql
DELIMITER $$

CREATE FUNCTION nama_fungsi(
    nama_parameter tipe_data
)
RETURNS tipe_data_kembalian
[CHARACTERISTICS]
BEGIN
    -- Deklarasi variabel penampung hasil
    DECLARE hasil tipe_data_kembalian;

    -- Logika / Rumus
    SET hasil = ...;

    -- Mengembalikan Nilai
    RETURN hasil;
END$$

DELIMITER ;
```

**Penjelasan Komponen:**

- `RETURNS tipe_data`: Mendefinisikan tipe data yang akan dikembalikan (misal: `INT`, `DECIMAL`, `VARCHAR`).
- `RETURN hasil`: Perintah wajib untuk mengembalikan nilai dari fungsi.
- `[CHARACTERISTICS]`: Atribut sifat fungsi (lihat tabel di bawah).

### Tabel Karakteristik (*Characteristics*)

| Characteristic | Artinya | Use Case |
| --- | --- | --- |
| **DETERMINISTIC** | Hasilnya **dijamin selalu sama** jika inputnya sama. | Kalkulasi matematis murni (hitung pajak, denda, diskon). |
| **NOT DETERMINISTIC** | Hasilnya **bisa berubah** meskipun inputnya sama. | Fungsi yang menggunakan `NOW()` atau `RAND()`. |
| **READS SQL DATA** | Fungsi ini berisi perintah `SELECT` untuk membaca tabel. | Fungsi *lookup* (mencari total data, mencari nama berdasarkan ID). |

### B. Menghapus Stored Function

```sql
DROP FUNCTION IF EXISTS nama_fungsi;
```

## 4. Praktik Pembuatan Stored Function (Studi Kasus Perpustakaan)

> **Catatan:** Sisipkan screenshot `Screenshot 2026-09-13 141101.png` pada bagian ini.

### Contoh 1: Fungsi Matematis Sederhana (`DETERMINISTIC`)

Kita akan membuat fungsi untuk menghitung denda keterlambatan. Asumsinya: denda adalah Rp2.000 per hari keterlambatan.

**Membuat Function:**

```sql
DELIMITER $$

CREATE FUNCTION HitungDenda(p_hari_terlambat INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_total_denda INT;

    IF p_hari_terlambat > 0 THEN
        SET v_total_denda = p_hari_terlambat * 2000;
    ELSE
        SET v_total_denda = 0;
    END IF;

    RETURN v_total_denda;
END$$

DELIMITER ;
```

**Cara Penggunaan dalam Query:**

```sql
-- Simulasi: Menghitung denda jika terlambat 5 hari
..

-- Simulasi: Menghitung denda jika tidak terlambat (0 hari)
SELECT HitungDenda(0) AS Total_Denda;
```

### Contoh 2: Fungsi Membaca Tabel / Lookup (`READS SQL DATA`)

Kita akan membuat fungsi untuk mengetahui berapa banyak buku yang **sedang dipinjam** (belum dikembalikan) oleh seorang anggota tertentu.

**Membuat Function:**

```sql
DELIMITER $$

CREATE FUNCTION TotalBukuDipinjam(p_id_anggota INT)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_jumlah_buku INT;

    -- Menghitung jumlah record di detail_peminjaman yang statusnya masih 'Dipinjam'
    SELECT COUNT(dp.id_detail) INTO v_jumlah_buku
    FROM detail_peminjaman dp
    JOIN peminjaman p ON dp.id_peminjaman = p.id_peminjaman
    WHERE p.id_anggota = p_id_anggota
      AND dp.status_kembali = 'Dipinjam';

    -- Mengembalikan 0 jika hasilnya NULL
    RETURN IFNULL(v_jumlah_buku, 0);
END$$

DELIMITER ;
```

**Cara Penggunaan dalam Query:**

Fungsi ini sangat berguna untuk ditampilkan sebagai kolom tambahan saat kita melakukan `SELECT` pada tabel anggota.

```sql
SELECT
    id_anggota,
    nama_anggota,
    TotalBukuDipinjam(id_anggota) AS buku_sedang_dipinjam
FROM anggota;
```

### Contoh 3: Fungsi dengan Nilai Dinamis (`NOT DETERMINISTIC`)

Kita akan membuat fungsi untuk membuat "Kode Transaksi" (ID Struk) yang menggabungkan teks, waktu saat ini, dan ID Peminjaman. Karena menggunakan tanggal hari ini (`NOW()`), nilainya akan selalu berubah, sehingga kita gunakan `NOT DETERMINISTIC`.

**Membuat Function:**

```sql
DELIMITER $$

CREATE FUNCTION BuatKodePeminjaman(p_id_peminjaman INT)
RETURNS VARCHAR(50)
NOT DETERMINISTIC
NO SQL
BEGIN
    -- Format: TRX/TahunBulanTanggal/ID_Peminjaman
    RETURN CONCAT('TRX/', DATE_FORMAT(NOW(), '%Y%m%d'), '/', p_id_peminjaman);
END$$

DELIMITER ;
```

**Cara Penggunaan dalam Query:**

```sql
SELECT
    id_peminjaman,
    BuatKodePeminjaman(id_peminjaman) AS kode_transaksi,
    tanggal_pinjam,
    batas_kembali
FROM peminjaman;
```
