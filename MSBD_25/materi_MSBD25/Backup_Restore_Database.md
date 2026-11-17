# Backup & Restore Database

## Pengertian Backup & Restore

**Backup** adalah proses membuat salinan lengkap dari data dan struktur database (termasuk tabel, view, prosedur, dan trigger) ke dalam sebuah file `.sql`. Tujuannya adalah untuk menjaga ketersediaan data dan meminimalkan risiko kehilangan data akibat kerusakan sistem, kesalahan pengguna, atau kegagalan perangkat keras.

Sementara itu, **Restore** adalah proses memulihkan database dari file cadangan `.sql` tersebut agar data kembali seperti semula.

Keduanya merupakan bagian penting dari strategi *data recovery* dan *disaster prevention*.

## Backup

### Sintaks Dasar Backup

```bash
mysqldump -u [username] -p [nama_database] > [nama_file_backup.sql]
```

**Penjelasan:**

- `mysqldump` → perintah untuk mengekspor struktur dan isi database MySQL.
- `-u` → menentukan user yang digunakan untuk koneksi ke MySQL.
- `-p` → menandakan bahwa sistem akan meminta password user.
- `>` → digunakan untuk mengarahkan hasil backup ke file tertentu.

**Contoh:**

```bash
mysqldump -u root -p perpustakaan > backup_perpustakaan.sql
```

### Praktik Melakukan Backup Database

#### Backup Menggunakan mysqldump

Jika ingin melakukan backup database bernama `perpustakaan` beserta semua *stored procedures*, *triggers*, dan *events*, gunakan perintah berikut.

**Windows**

```bash
mysqldump -u root -p --single-transaction --routines --events perpustakaan > D:\backup_perpustakaan.sql
```

**macOS / Linux**

```bash
mysqldump -u root -p --single-transaction --routines --events perpustakaan > ~/Documents/backup_perpustakaan.sql
```

**Keterangan tambahan:**

- `--single-transaction` → melakukan backup tanpa mengunci tabel (untuk tabel transaksional seperti InnoDB).
- `--routines` → menyertakan *stored procedures* dan *functions*.
- `--events` → menyertakan *events scheduler*.
- `> path/file.sql` → lokasi penyimpanan hasil backup.

#### Backup Menggunakan phpMyAdmin

Backup juga dapat dilakukan melalui phpMyAdmin tanpa menggunakan terminal:

1. Masuk ke phpMyAdmin.
2. Pilih database `perpustakaan` yang ingin di-backup.
3. Klik tab **Export**.
4. Pilih format **SQL** (default).
5. Klik tombol **Go / Export** untuk menyimpan file hasil backup.

## Restore

### Sintaks Dasar Restore

```bash
mysql -u [username] -p [nama_database] < [nama_file_backup.sql]
```

**Penjelasan:**

- `mysql` → perintah untuk menjalankan perintah SQL di MySQL.
- `<` → digunakan untuk membaca isi file backup dan menjalankannya pada database yang dituju.

### Praktik Melakukan Restore Database

**Windows CMD**

```bash
mysql -u root -p perpustakaan < D:\backup_perpustakaan.sql
```

**PowerShell**

```powershell
Get-Content D:\backup_perpustakaan.sql | mysql -u root -p perpustakaan
```

**macOS / Linux**

```bash
mysql -u root -p perpustakaan < ~/Documents/backup_perpustakaan.sql
```
