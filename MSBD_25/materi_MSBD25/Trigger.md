# Trigger

## Pengertian Trigger

**Trigger** adalah kumpulan perintah SQL yang berjalan secara otomatis (*terpicu*) ketika ada kejadian (*event*) tertentu pada sebuah tabel. Ibaratnya, Trigger adalah "penjaga" yang akan otomatis bereaksi jika ada data yang ditambah, diubah, atau dihapus.

## Syntax Dasar TRIGGER

Berikut adalah bentuk baku (syntax) saat membuat Trigger di MySQL:

```sql
DELIMITER $$

CREATE TRIGGER [nama_trigger]
[BEFORE | AFTER] [INSERT | UPDATE | DELETE] ON [nama_tabel]
FOR EACH ROW
BEGIN
    -- aksi atau logika SQL yang ingin dijalankan
END$$

DELIMITER ;
```

**Keterangan Keyword:**

- **`BEFORE`** → Trigger berjalan *sebelum* query utama dieksekusi masuk ke tabel.
- **`AFTER`** → Trigger berjalan *setelah* query utama selesai dieksekusi masuk ke tabel.
- **`INSERT / UPDATE / DELETE`** → Event (kejadian) yang memicu Trigger.
- **`NEW`** → Mengambil nilai data **baru** (hanya tersedia untuk `INSERT` dan `UPDATE`).
- **`OLD`** → Mengambil nilai data **lama** (hanya tersedia untuk `UPDATE` dan `DELETE`).

## Perbedaan BEFORE dan AFTER

Untuk memudahkan kapan harus memakai `BEFORE` dan kapan memakai `AFTER`, perhatikan tabel perbandingan berikut:

| **Jenis Trigger** | **Waktu Eksekusi** | **Data yang Diakses** | **Fungsi Utama & Sifat Pembatalan** | **Contoh penggunaan** |
| --- | --- | --- | --- | --- |
| **BEFORE INSERT** | **Sebelum** data baru disimpan ke tabel utama. | `NEW` | **Validasi data.** *(Bisa membatalkan proses query)*. | Validasi data sebelum disimpan |
| **AFTER INSERT** | **Setelah** data baru berhasil disimpan di tabel. | `NEW` | **Sinkronisasi tabel lain.** *(Tidak bisa membatalkan proses)*. | Catat log pelanggan baru |
| **BEFORE UPDATE** | **Sebelum** data lama diganti dengan data baru. | `OLD`, `NEW` | **Validasi / manipulasi nilai.** *(Bisa membatalkan proses query)*. | Simpan nilai lama sebelum diubah |
| **AFTER UPDATE** | **Setelah** data berhasil diubah di tabel utama. | `OLD`, `NEW` | **Sinkronisasi & Log riwayat.** *(Tidak bisa membatalkan proses)*. | Catat log perubahan harga |
| **BEFORE DELETE** | **Sebelum** data dihapus dari tabel utama. | `OLD` | **Proteksi data.** *(Bisa membatalkan proses query)*. | Cegah penghapusan jika masih dipakai |
| **AFTER DELETE** | **Setelah** data berhasil dihapus dari tabel. | `OLD` | **Pembersihan & pencatatan log.** *(Tidak bisa membatalkan proses)*. | Catat log bahwa pesanan sudah dihapus |

## Studi Kasus: Database Perpustakaan

![ChatGPT Image 13 Sep 2026, 15.32.45.png](ChatGPT%20Image%2013%20Sep%202026%2C%2015.32.45.png)

### 🔹 1. Trigger AFTER INSERT (Otomatis Kurangi Stok Saat Dipinjam)

**Tujuan:** Setiap kali petugas memasukkan data peminjaman baru ke tabel `detail_peminjaman`, stok di tabel `buku` otomatis berkurang 1.

```sql
DELIMITER $$

CREATE TRIGGER trg_kurangi_stok
AFTER INSERT ON detail_peminjaman
FOR EACH ROW
BEGIN
    -- Kurangi stok buku hanya jika statusnya 'Dipinjam'
    IF NEW.status_kembali = 'Dipinjam' THEN
        UPDATE buku
        SET stok = stok - 1
        WHERE id_buku = NEW.id_buku;
    END IF;
END$$

DELIMITER ;
```

```sql
INSERT INTO detail_peminjaman (id_peminjaman, id_buku, status_kembali)
VALUES (5, 1, 'Dipinjam');
```

### 🔹 2. Trigger AFTER UPDATE (Otomatis Kembalikan Stok)

**Tujuan:** Ketika buku dikembalikan, petugas akan men-setting status dari 'Dipinjam' menjadi 'Dikembalikan'. Saat itu terjadi, stok buku harus bertambah kembali.

```sql
DELIMITER $$

CREATE TRIGGER trg_kembalikan_stok
AFTER UPDATE ON detail_peminjaman
FOR EACH ROW
BEGIN
    -- Cek jika status sebelumnya 'Dipinjam' dan diubah jadi 'Dikembalikan'
    IF OLD.status_kembali = 'Dipinjam' AND NEW.status_kembali = 'Dikembalikan' THEN
        UPDATE buku
        SET stok = stok + 1
        WHERE id_buku = NEW.id_buku;
    END IF;
END$$

DELIMITER ;
```

```sql
UPDATE detail_peminjaman
SET status_kembali = 'Dikembalikan'
WHERE id_detail = 3;
```

### 🔹 3. Trigger BEFORE INSERT (Validasi Pencegahan Stok Kosong)

**Tujuan:** Mencegah terjadinya transaksi peminjaman jika stok buku yang dipilih sedang habis (0). Database akan otomatis menolak dan memunculkan error.

```sql
DELIMITER $$

CREATE TRIGGER trg_validasi_stok
BEFORE INSERT ON detail_peminjaman
FOR EACH ROW
BEGIN
    DECLARE sisa_stok INT;

    -- Ambil stok buku saat ini dan simpan di variabel sisa_stok
    SELECT stok INTO sisa_stok FROM buku WHERE id_buku = NEW.id_buku;

    -- Jika stok 0, batalkan proses dan tampilkan pesan error
    IF sisa_stok <= 10 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal Peminjaman: Stok buku sudah habis!';
    END IF;
END$$

DELIMITER ;
```

```sql
INSERT INTO detail_peminjaman (id_peminjaman, id_buku, status_kembali)
VALUES (1, 2, 'Dipinjam');
```

## Manajemen Trigger

**1. Melihat daftar Trigger yang ada di database:**

```sql
SHOW TRIGGERS;
```

**2. Menghapus Trigger:**

```sql
DROP TRIGGER [IF EXISTS] [nama_trigger];
```

### Mana yang Lebih Baik? (Trigger vs Tanpa Trigger)

Keduanya adalah praktik yang sah di dunia industri, namun memiliki peruntukan yang berbeda:

- **Menggunakan Trigger:** Sangat bagus jika Anda ingin kode *backend* aplikasi Anda bersih dan pendek. Cukup kirim 1 perintah, database langsung merespon semuanya. Namun, kelemahannya adalah logika bisnis menjadi "tersembunyi" di dalam database, yang kadang membuat *programmer* baru kebingungan mencari sumber *bug*.
- **Tanpa Trigger (Manual di Aplikasi):** Membutuhkan penulisan kode yang lebih panjang di sisi aplikasi. Namun, ini adalah **standar industri modern** (terutama jika menggunakan *Framework* yang canggih) karena semua logika terpusat di dalam kode kodingan, sehingga sangat mudah untuk dilacak, di-tes, dan diatur versinya.

## 5. Kesimpulan

- **TRIGGER** = prosedur otomatis yang jalan saat ada event (INSERT, UPDATE, DELETE).
- Bisa dipakai untuk:
    - Menjaga **konsistensi data**
    - Membuat **log perubahan**
    - Melakukan **validasi otomatis**
- `BEFORE` dan `AFTER` menentukan kapan trigger dijalankan.
- `OLD` dan `NEW` dipakai untuk mengakses data lama/baru.
