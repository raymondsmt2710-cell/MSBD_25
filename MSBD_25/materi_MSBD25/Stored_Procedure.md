# Stored Procedure

## 1. Pengertian Stored Procedure

**Stored Procedure** adalah sekumpulan perintah SQL yang disimpan di dalam database untuk mengeksekusi suatu aksi kompleks. Procedure bisa melakukan manipulasi data (`INSERT`, `UPDATE`, `DELETE`), menerima parameter, dan tidak wajib mengembalikan nilai. Procedure dapat dipanggil kapan saja dengan memanggil namanya.

### Tujuan dan Keuntungan Utama

- **Reusability (Dapat Digunakan Kembali):** Mengurangi penulisan query berulang. Cukup buat satu kali di database, lalu panggil berkali-kali dari berbagai aplikasi.
- **Menyembunyikan Kompleksitas:** Memungkinkan otomatisasi langkah-langkah yang kompleks. Aplikasi cukup memanggil nama procedure tanpa perlu mengetahui detail tabel yang saling berelasi di dalamnya.
- **Mengurangi Lalu Lintas Jaringan (Network Traffic):** Cukup mengirim satu perintah `CALL` dari aplikasi ke database, dibandingkan mengirim puluhan baris teks SQL untuk setiap operasi.

## 2. Sintaks Dasar Stored Procedure

### A. Membuat Stored Procedure

```sql
DELIMITER $$

CREATE PROCEDURE nama_procedure(
    [IN | OUT | INOUT] nama_parameter tipe_data
)
BEGIN
    -- Blok Kode SQL
END$$

DELIMITER ;
```

**Penjelasan Komponen:**

- `DELIMITER $$`: Secara *default*, MySQL menggunakan titik koma (`;`) untuk mengakhiri perintah. Karena di dalam procedure kita akan menggunakan banyak titik koma, penanda akhir diubah sementara menjadi `$$` agar MySQL tidak memotong perintah saat mendefinisikan procedure.
- `CREATE PROCEDURE nama_procedure(...)`: Perintah utama untuk membuat procedure baru.
- `BEGIN ... END`: Blok yang membungkus semua logika dan perintah SQL.
- `[IN | OUT | INOUT]`:
  - **IN:** Parameter input. Nilai diberikan saat memanggil procedure dan bersifat *read-only* (tidak diubah di dalam procedure).
  - **OUT:** Parameter output. Nilai diatur di dalam procedure dan dikembalikan setelah procedure selesai dijalankan.
  - **INOUT:** Parameter yang menerima nilai input, bisa diubah di dalam procedure, dan hasil perubahannya dikembalikan sebagai output.

### B. Menghapus Stored Procedure

Jika ingin mengubah isi procedure, hapus procedure tersebut terlebih dahulu, lalu buat kembali.

```sql
DROP PROCEDURE IF EXISTS nama_procedure;
```

## 3. Praktik Pembuatan Stored Procedure (Logika Bisnis)

### 1. Parameter IN (Input)

Digunakan untuk **memberikan input** ke dalam *procedure*. Nilai dikirim dari pemanggil ke dalam *procedure* dan bersifat *read-only* (hanya dibaca, tidak diubah di dalam procedure untuk dikembalikan).

**Contoh: Membuat Procedure Sapaan**

```sql
DELIMITER $$

CREATE PROCEDURE contoh_in(IN nama VARCHAR(50))
BEGIN
    SELECT CONCAT('Halo, ', nama, '! Selamat belajar Stored Procedure.') AS sapaan;
END$$

DELIMITER ;
```

**Cara Pemanggilan:**

```sql
CALL contoh_in('Andre');

-- Output tabel akan menampilkan pesan:
-- "Halo, Andre! Selamat belajar Stored Procedure."
```

### 2. Parameter OUT (Output)

Digunakan untuk **mengembalikan hasil** dari *procedure* ke pemanggilnya. Kita memasukkan variabel kosong saat memanggil, lalu *procedure* akan mengisi variabel tersebut dengan sebuah nilai.

**Contoh: Membuat Procedure Penjumlahan**

```sql
DELIMITER $$

CREATE PROCEDURE contoh_out(
    IN angka1 INT,
    IN angka2 INT,
    OUT hasil INT
)
BEGIN
    -- Hasil dari angka1 + angka2 akan dimasukkan ke parameter OUT 'hasil'
    SET hasil = angka1 + angka2;
END$$

DELIMITER ;
```

**Cara Pemanggilan:**

```sql
-- Siapkan variabel untuk menampung hasil
CALL contoh_out(10, 20, @total);

-- Tampilkan isi variabel @total
SELECT @total AS Hasil_Penjumlahan;

-- Output: 30
```

### 3. Parameter INOUT (Input & Output)

Merupakan gabungan dari IN dan OUT. Parameter ini **menerima nilai awal** dari luar, lalu nilai tersebut **diubah/diproses** di dalam *procedure*, dan **hasil perubahannya dikembalikan** lagi ke pemanggil menggunakan variabel yang sama.

**Contoh: Membuat Procedure Perkalian**

```sql
DELIMITER $$

CREATE PROCEDURE contoh_inout(INOUT angka INT)
BEGIN
    -- Nilai yang masuk dikalikan 2, lalu disimpan kembali ke variabel 'angka'
    SET angka = angka * 2;
END$$

DELIMITER ;
```

**Cara Pemanggilan:**

```sql
-- 1. Tentukan nilai awal
SET @nilai = 5;

-- 2. Panggil procedure dan masukkan variabel
CALL contoh_inout(@nilai);

-- 3. Tampilkan nilai akhir setelah dikalikan 2
SELECT @nilai AS Hasil_Akhir;

-- Output: 10
```

## 4. Persiapan Database (Studi Kasus: Perpustakaan)

> **Catatan:** Sisipkan screenshot `Screenshot 2026-09-13 141101.png` pada bagian ini.

### Parameter INOUT untuk Perpanjang Masa Pinjam

**Skenario:** Anggota ingin memperpanjang waktu pinjam. Nilai `INOUT` membawa permintaan tambahan hari. Namun, perpustakaan membatasi **maksimal perpanjangan adalah 7 hari**. Jika anggota meminta lebih dari 7 hari, sistem akan membatasinya menjadi 7 hari.

**Membuat Stored Procedure:**

```sql
DELIMITER $$

CREATE PROCEDURE PerpanjangPeminjamanBuku(
    IN p_nama_anggota VARCHAR(100),
    IN p_judul_buku VARCHAR(150),
    INOUT p_tambahan_hari INT,
    OUT p_tgl_kembali_baru DATE
)
BEGIN
    DECLARE v_id_peminjaman INT;

    -- 1. Cari otomatis ID transaksinya berdasarkan nama dan judul buku
    SELECT p.id_peminjaman INTO v_id_peminjaman
    FROM peminjaman p
    JOIN anggota a ON p.id_anggota = a.id_anggota
    JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
    JOIN buku b ON dp.id_buku = b.id_buku
    WHERE a.nama_anggota = p_nama_anggota
      AND b.judul_buku = p_judul_buku
      AND dp.status_kembali = 'Dipinjam'
    LIMIT 1;

    -- 2. Jika transaksi ditemukan (sedang dipinjam), lakukan perpanjangan
    IF v_id_peminjaman IS NOT NULL THEN

        -- Pembatasan perpanjangan maksimal 7 hari
        IF p_tambahan_hari > 7 THEN
            SET p_tambahan_hari = 7;
        END IF;

        -- Update batas pengembalian di tabel
        UPDATE peminjaman
        SET batas_kembali = DATE_ADD(
            batas_kembali,
            INTERVAL p_tambahan_hari DAY
        )
        WHERE id_peminjaman = v_id_peminjaman;

        -- Simpan hasil tanggal baru ke parameter OUT
        SELECT batas_kembali INTO p_tgl_kembali_baru
        FROM peminjaman
        WHERE id_peminjaman = v_id_peminjaman;

    ELSE
        -- Jika buku sudah dikembalikan atau nama/buku salah
        SET p_tambahan_hari = 0;
        SET p_tgl_kembali_baru = NULL;
    END IF;

END$$

DELIMITER ;
```

**Cara Pemanggilan:**

```sql
-- 1. Siapkan semua data input ke dalam variabel
SET @nama_peminjam = 'Budi';
SET @judul_buku = 'MSBD';
SET @hari = 10;

-- 2. Panggil procedure menggunakan variabel tersebut
CALL PerpanjangPeminjamanBuku(
    @nama_peminjam,
    @judul_buku,
    @hari,
    @tgl_baru
);

-- 3. Tampilkan laporan menggunakan variabel yang sama
SELECT
    @nama_peminjam AS Peminjam,
    @judul_buku AS Buku_Diperpanjang,
    @hari AS Hari_Disetujui,
    @tgl_baru AS Tanggal_Kembali_Baru;
```
