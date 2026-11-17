# Manajemen User dan Hak Akses Dalam MySQL

## Pengertian

**Manajemen User** adalah pengelolaan akun-akun pengguna database, yang
meliputi pembuatan akun, pengelolaan hak akses akun, penghapusan akun
untuk memastikan setiap pengguna memiliki akses yang tepat dan sesuai
kebutuhan dari database. Manajemen User berfungsi untuk menjaga keamanan
dan integritas data dari akses yang tidak sah.

Skema Database

![Skema Database](assets_manajemen_user/skema-database.png)

Silahkan jalankan pada Command Prompt / PhpMyAdmin satu per satu mulai
dari pembuatan database, pembuatan tabel, dan pengisian tabel.

``` sql
-- Langkah 1 : Buat Database
CREATE DATABASE perpustakaan;
USE perpustakaan;

-- Langkah 2 : Buat Tabel
CREATE TABLE buku (
    id_buku INT AUTO_INCREMENT PRIMARY KEY,
    judul_buku VARCHAR(150),
    kategori VARCHAR(50),
    stok INT
);

CREATE TABLE anggota (
    id_anggota INT AUTO_INCREMENT PRIMARY KEY,
    nama_anggota VARCHAR(100),
    program_studi VARCHAR(100)
);

CREATE TABLE peminjaman (
    id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota INT,
    tanggal_pinjam DATE,
    batas_kembali DATE,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota)
);

CREATE TABLE detail_peminjaman (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT,
    id_buku INT,
    status_kembali ENUM('Dipinjam', 'Dikembalikan') DEFAULT 'Dipinjam',
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman),
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
);

-- Langkah 3 : Masukkan Data
INSERT INTO buku (judul_buku, kategori, stok) VALUES
('MSBD', 'Teknologi Informasi', 10),
('SDA', 'Ilmu Komputer', 5),
('RPL', 'Teknologi Informasi', 8);

INSERT INTO anggota (nama_anggota, program_studi) VALUES
('Budi', 'Teknologi Informasi'),
('Siti', 'Sistem Informasi'),
('Reza', 'Ilmu Komputer');

INSERT INTO peminjaman (id_anggota, tanggal_pinjam, batas_kembali) VALUES
(1, '2026-09-01', '2026-09-08'),
(2, '2026-09-02', '2026-09-09'),
(1, '2026-09-05', '2026-09-12');

INSERT INTO detail_peminjaman (id_peminjaman, id_buku, status_kembali) VALUES
(1, 1, 'Dikembalikan'),
(1, 2, 'Dikembalikan'),
(2, 3, 'Dipinjam'),
(3, 1, 'Dipinjam');
```

# Praktik Manajemen User

## . Membuat User Baru

Pada saat login ke database, biasanya kita menggunakan akun superadmin
(root). Superadmin memiliki seluruh privileges dalam database sehingga,
penting bagi kita untuk membuat user baru apabila database yang kita
rancang melibatkan beberapa level pengguna.

``` sql
CREATE USER 'pustakawan'@'localhost' IDENTIFIED BY 'pustaka2026';
```

## 2. Memberikan Hak Akses (Privilege)

Setelah berhasil membuat user baru, user tersebut perlu diberikan hak
akses agar dapat mengakses basis data. Perlu diingat, pemberian hak
akses hanya boleh **sesuai kepentingan dari user**. User hanya akan
dapat berinteraksi sesuai dengan hak atau limitasi hak yang telah kita
buat.

  -----------------------------------------------------------------------
  Jenis Hak Akses         Kategori                Deskripsi / Fungsi
  (Privilege)                                     
  ----------------------- ----------------------- -----------------------
  `SELECT`                Data (DML)              Mengizinkan *user*
                                                  untuk membaca atau
                                                  melihat data dari
                                                  tabel.

  `INSERT`                Data (DML)              Mengizinkan *user*
                                                  untuk menambahkan baris
                                                  data baru ke dalam
                                                  tabel.

  `UPDATE`                Data (DML)              Mengizinkan *user*
                                                  untuk mengubah data
                                                  yang sudah ada di dalam
                                                  tabel.

  `DELETE`                Data (DML)              Mengizinkan *user*
                                                  untuk menghapus baris
                                                  data dari tabel.

  `CREATE`                Struktur (DDL)          Mengizinkan *user*
                                                  untuk membuat
                                                  *database* atau tabel
                                                  baru.

  `ALTER`                 Struktur (DDL)          Mengizinkan *user*
                                                  untuk memodifikasi
                                                  struktur tabel yang
                                                  sudah ada (menambah
                                                  kolom, mengubah tipe
                                                  data).

  `DROP`                  Struktur (DDL)          Mengizinkan *user*
                                                  untuk menghapus
                                                  *database* atau tabel
                                                  secara permanen.

  `INDEX`                 Struktur (DDL)          Mengizinkan *user*
                                                  untuk membuat atau
                                                  menghapus *index* pada
                                                  tabel (biasanya untuk
                                                  optimasi pencarian).

  `ALL PRIVILEGES`        Administrasi            Memberikan semua hak
                                                  akses di atas kepada
                                                  *user* (kecuali
                                                  kemampuan memberikan
                                                  hak ke orang lain).

  `GRANT OPTION`          Administrasi            Mengizinkan *user*
                                                  tersebut untuk
                                                  memberikan hak akses
                                                  yang ia miliki kepada
                                                  *user lain*.
  -----------------------------------------------------------------------

``` sql
GRANT ALL PRIVILEGES ON perpustakaan.* TO 'pustakawan'@'localhost';
```

## 3. Melihat Hak Akses User

Setelah berhasil memberikan hak akses kepada user yang baru dibuat, kita
dapat melihat jenis hak akses yang diberikan.

``` sql
SHOW GRANTS FOR 'pustakawan'@'localhost';
```

## 4. Menghapus Hak Akses User

Hak akses seorang user juga dapat dihapus (revoke) di database MySQL.
Hal ini bertujuan untuk memperbaiki apabila hak akses yang dibebankan
pada suatu user melebihi dari apa yang seharusnya diberikan.

``` sql
REVOKE INSERT, UPDATE ON perpustakaan.* FROM 'pustakawan'@'localhost';
```

## 5. Menghapus User pada MySQL

Apabila kita sudah tidak membutuhkan user, maka dalam MySQL kita dapat
menghapus user tersebut. Hal ini diperlukan agar user tersebut tidak
akan menjadi celah keamanan dalam database. Dengan menghapus user yang
tidak terpakai, kontrol manajemen user menjadi lebih rapi dan mengurangi
risiko kesalahan.

``` sql
DROP USER 'pustakawan'@'localhost';
```
