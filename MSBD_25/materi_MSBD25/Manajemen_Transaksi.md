# Manajemen Transaksi

**Transaksi** adalah sekumpulan operasi database (seperti `INSERT`, `UPDATE`, `DELETE`) yang dieksekusi sebagai **satu kesatuan kerja tunggal** (*Single Unit of Work*).

Prinsip dasarnya adalah:

> “Semua atau tidak sama sekali” (*all or nothing*).

Artinya, jika ada lima perintah SQL dalam satu transaksi, maka kelimanya harus berhasil. Jika satu saja gagal, seluruh perintah yang sudah terlanjur berjalan harus dibatalkan dan data dikembalikan ke kondisi semula.

## Komponen Kunci Transaksi: Properti ACID

Keandalan sebuah sistem database dijamin oleh empat properti utama yang disebut **ACID**.

| Komponen | Artinya | Penjelasan |
| --- | --- | --- |
| **Atomicity** | Semua atau tidak sama sekali | Jika satu operasi gagal, semua operasi dalam transaksi ikut dibatalkan. |
| **Consistency** | Konsistensi data terjaga | Setelah transaksi selesai, data tetap berada dalam keadaan valid, sesuai aturan dan constraint. |
| **Isolation** | Transaksi tidak saling mengganggu | Setiap transaksi berjalan seolah-olah hanya dirinya yang aktif. |
| **Durability** | Perubahan bersifat permanen | Setelah `COMMIT`, data akan tersimpan secara permanen walau sistem gagal. |

## TCL (Transaction Control Language)

TCL digunakan untuk **mengontrol jalannya transaksi** di MySQL.

| Perintah | Fungsi | Status Transaksi |
| --- | --- | --- |
| `START TRANSACTION` | Menginisialisasi awal transaksi | Transaksi **dimulai** |
| `COMMIT` | Menyimpan semua perubahan secara permanen | Transaksi **berakhir (sukses)** |
| `ROLLBACK` | Membatalkan semua perubahan | Transaksi **berakhir (dibatalkan)** |
| `SAVEPOINT` | Menandai titik checkpoint di dalam transaksi | Transaksi **tetap berjalan** |
| `ROLLBACK TO SAVEPOINT` | Kembali ke titik checkpoint | Transaksi **tetap berjalan** |

## Pseudocode Manajemen Transaksi

```sql
START TRANSACTION;

-- SELECT
-- INSERT
SAVEPOINT svp1;

-- SELECT
-- UPDATE
-- INSERT
-- UPDATE

IF (gagal) THEN
    ROLLBACK TO SAVEPOINT svp1;
ELSE
    COMMIT;
END IF;
```

## 1. Membuat Skema Database & Integrity Lanjutan

Kita akan membuat 3 tabel: `pelanggan`, `produk`, dan `pembelian`. Di sini kita memasang aturan ketat (*constraint*) agar data tidak bisa minus dan menjaga hubungan antartabel.

```sql
CREATE DATABASE toko_online;
USE toko_online;

CREATE TABLE pelanggan (
    id_pelanggan VARCHAR(10) PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    saldo DECIMAL(15, 2) NOT NULL,

    -- [Integrity Lanjutan]: Saldo tidak boleh di bawah 0
    CONSTRAINT chk_saldo_positif CHECK (saldo >= 0)
);

CREATE TABLE produk (
    id_produk VARCHAR(10) PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    harga DECIMAL(15, 2) NOT NULL,
    stok INT NOT NULL,

    -- [Integrity Lanjutan]: Stok tidak boleh minus
    CONSTRAINT chk_stok_positif CHECK (stok >= 0)
);

CREATE TABLE pembelian (
    id_pembelian INT PRIMARY KEY AUTO_INCREMENT,
    id_pelanggan VARCHAR(10),
    id_produk VARCHAR(10),
    qty INT NOT NULL,
    total_bayar DECIMAL(15, 2) NOT NULL,
    tanggal_beli TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- [Integrity Lanjutan]:
    -- Jika pelanggan dihapus, riwayat belinya ikut terhapus (CASCADE)
    -- Jika produk masih ada di riwayat beli, produk tidak bisa dihapus (RESTRICT)
    CONSTRAINT fk_pelanggan FOREIGN KEY (id_pelanggan)
        REFERENCES pelanggan(id_pelanggan) ON DELETE CASCADE,
    CONSTRAINT fk_produk FOREIGN KEY (id_produk)
        REFERENCES produk(id_produk) ON DELETE RESTRICT
);

-- Memasukkan data awal (dummy)
INSERT INTO pelanggan (id_pelanggan, nama_pelanggan, saldo) VALUES
('P01', 'Rina', 500000.00),
('P02', 'Budi', 100000.00);

INSERT INTO produk (id_produk, nama_produk, harga, stok) VALUES
('BRG01', 'Keyboard Mekanikal', 350000.00, 10),
('BRG02', 'Mouse Gaming', 50000.00, 5),
('BRG03', 'Flashdisk 64GB', 40000.00, 0);
```

### Penjelasan Skema Database & Integrity Lanjutan

Pada pembuatan tabel di atas, kita menanamkan aturan otomatis (*constraint*) agar database bisa menolak data yang salah atau merusak sistem:

1. **`CHECK` Constraint (Mencegah Data Minus):** Aturan `CHECK (saldo >= 0)` dan `CHECK (stok >= 0)` memastikan saldo pelanggan dan stok barang tidak mungkin bernilai negatif. Jika ada transaksi yang membuat saldo atau stok menjadi minus, database akan langsung menolaknya secara otomatis.
2. **`ON DELETE CASCADE` (Hapus Berantai):** Pada tabel pembelian, jika data seorang **pelanggan** dihapus, seluruh riwayat belanjanya akan otomatis ikut terhapus. Ini mencegah adanya data "sampah" atau nota tanpa nama pembeli.
3. **`ON DELETE RESTRICT` (Blokir Penghapusan):** Jika sebuah **produk** sudah pernah dibeli dan tercatat di riwayat pembelian, produk tersebut *tidak boleh dihapus* dari database. Aturan ini menjaga agar laporan riwayat belanja tidak kacau karena kehilangan referensi barang.

## 2. Membuat Procedure Manajemen Transaksi

Stored Procedure ini bertugas memproses pembelian. Jika terjadi kesalahan (misalnya karena terbentur *constraint* `CHECK` di database atau sistem mati), instruksi `ROLLBACK` akan mengamankan data.

```sql
DELIMITER //

CREATE PROCEDURE sp_CheckoutBarang(
    IN p_id_pelanggan VARCHAR(10),
    IN p_id_produk VARCHAR(10),
    IN p_qty INT,
    IN p_total_harga DECIMAL(15, 2)
)
BEGIN
    -- Menangani kegagalan utama: Jika terjadi error SQL (misalnya stok/saldo tidak cukup),
    -- batalkan seluruh perubahan data (ROLLBACK) dan tampilkan pesan pembatalan.
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Transaksi Batal Total: Saldo atau Stok tidak mencukupi!' AS Status;
    END;

    -- Memulai transaksi agar semua perintah SQL berikutnya berjalan dalam satu siklus ACID.
    START TRANSACTION;

    -- Mengurangi stok barang yang dibeli berdasarkan ID produk.
    UPDATE produk
    SET stok = stok - p_qty
    WHERE id_produk = p_id_produk;

    -- Mengurangi saldo pelanggan sesuai total harga belanjaan.
    UPDATE pelanggan
    SET saldo = saldo - p_total_harga
    WHERE id_pelanggan = p_id_pelanggan;

    -- Mencatat data transaksi baru ke dalam tabel riwayat pembelian.
    INSERT INTO pembelian (id_pelanggan, id_produk, qty, total_bayar)
    VALUES (p_id_pelanggan, p_id_produk, p_qty, p_total_harga);

    -- Membuat penanda (savepoint) setelah proses transaksi utama sukses.
    SAVEPOINT titik_aman_utama;

    BEGIN
        -- Menangani kegagalan opsional: Jika stok bonus habis/error,
        -- kembalikan kondisi data ke titik aman utama tanpa membatalkan transaksi pembelian utama.
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK TO titik_aman_utama;
        END;

        -- Mencoba mengurangi 1 stok barang bonus (Flashdisk/BRG03) jika masih tersedia.
        UPDATE produk
        SET stok = stok - 1
        WHERE id_produk = 'BRG03';
    END;

    -- Menyimpan seluruh perubahan secara permanen ke dalam basis data.
    COMMIT;

    -- Mengembalikan status konfirmasi bahwa seluruh proses transaksi telah selesai.
    SELECT 'Transaksi Selesai ' AS Status;

END //

DELIMITER ;
```

### Cara Praktikan Memanggilnya

```sql
-- Rina beli Keyboard (harga 350.000 x 1)
CALL sp_CheckoutBarang('P01', 'BRG01', 1, 350000);
```
