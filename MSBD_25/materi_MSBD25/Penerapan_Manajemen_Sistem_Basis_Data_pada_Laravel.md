# Penerapan Manajemen Sistem Basis Data pada Laravel

## Tujuan Praktikum

- Membuat dan menjalankan Stored Procedure serta Stored Function pada MySQL.
- Membuat dan menggunakan Database View untuk menyederhanakan query.
- Mengimplementasikan Trigger untuk pencatatan aktivitas otomatis.
- Menerapkan transaksi database menggunakan `COMMIT` dan `ROLLBACK`.
- Mengelola hak akses pengguna database menggunakan `GRANT`.
- Mengintegrasikan pengelolaan hak akses database dengan Laravel.

### Hubungan MVC dengan integrasi MSBD

Di praktikum ini, objek database dibuat di **MySQL**, lalu dipanggil dari Laravel:

| Objek Database | Dibuat di | Dipanggil dari | Cara memanggil di Laravel |
| --- | --- | --- | --- |
| Stored Procedure | Migration (`DB::unprepared`) | `Controller` | `DB::statement('CALL nama_procedure(?, ?)', [...])` |
| Stored Function | Migration (`DB::unprepared`) | `Model` | `DB::selectOne('SELECT nama_function() AS hasil')` |
| View | Migration (`DB::unprepared`) | `Controller` | `DB::table('nama_view')->get()` |
| Trigger | Migration (`DB::unprepared`) | Tidak dipanggil | Otomatis saat `INSERT` / `UPDATE` / `DELETE` pada tabel |
| Transaction | Bawaan MySQL  | `Controller` | `DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()` |
| User & GRANT | Migration (`CREATE USER`, `GRANT`) + `.env` + `config/database.php` | `Middleware` | `DB::setDefaultConnection($targetConnection)` |
- **`DB::unprepared()`** dipakai di **Migration** untuk **membuat** objek database (`CREATE PROCEDURE`, `CREATE FUNCTION`, `CREATE VIEW`, `CREATE TRIGGER`, `GRANT`). SQL dikirim mentah karena MySQL menolak perintah `CREATE` tersebut jika memakai tanda `?`.
- **`DB::statement()`** dipakai di **Controller** untuk **memanggil** Stored Procedure (`CALL nama_procedure(?, ?)`). Tanda `?` membuat input pengguna aman dari **SQL Injection**.
- **`DB::selectOne()`** dipakai di **Model** untuk **mengambil nilai** dari Stored Function (`SELECT nama_function() AS hasil`).
- **`DB::table()`** dipakai di **Controller** untuk **membaca** View seperti tabel biasa (`DB::table('nama_view')->get()`).
- **`DB::beginTransaction()`**, **`DB::commit()`**, dan **`DB::rollBack()`** dipakai di **Controller** agar beberapa query **berhasil bersama atau batal bersama**.
- **`DB::setDefaultConnection()`** dipakai di **Middleware** untuk **mengganti** user MySQL sesuai role yang login.

### Kenapa diletakkan di Controller atau di Model?

- **Controller** menangani satu aksi pengguna (klik "Batalkan", klik "Proses Pesanan"). Karena itu pemanggilan **procedure** dan **transaction** diletakkan di controller: controller tahu kapan sebuah aksi dimulai dan selesai.
- **Model** mewakili data. Perhitungan yang menyangkut data transaksi (omzet, jumlah pesanan) adalah tanggung jawab model `Transaction`, sehingga **function** dipanggil dari model. Controller cukup memanggil NamaModel::namaMethod().
- **Trigger** tidak punya kode Laravel sama sekali. Trigger hidup di dalam database dan berjalan sendiri setiap ada perubahan data.

## LANGKAH 0 — Setup Awal dan Database Seeder

Sebelum memulai praktikum, pastikan project Laravel sudah terpasang dan konfigurasi database pada file `.env` sudah benar.

### 1. Jalankan Migrasi dan Seeder

Buka terminal pada direktori project Laravel, kemudian jalankan:

```
php artisan migrate:fresh --seed
```

### 2. Tabel yang Digunakan

Database praktikum menggunakan tabel berikut:

- `users`
- `customers`
- `products`
- `transactions`
- `transaction_details`
- `log_pembatalan`
- `log_aktivitas`

Tabel tambahan seperti `cache`, `sessions`, dan `migrations` juga dapat digunakan sesuai konfigurasi project.

## 1 — Stored Procedure (Log Pembatalan Pesanan)

### 1.1. Konsep

Pada praktikum ini, Stored Procedure digunakan untuk mencatat alasan pembatalan pesanan ke tabel `log_pembatalan`. Procedure akan memeriksa apakah riwayat pembatalan untuk transaksi tersebut sudah tersedia sebelum menambahkan catatan baru.

### 1.2. Buat Migration Stored Procedure

Jalankan perintah berikut:

```php
php artisan make:migration create_log_pembatalan_procedure
```

Buka file migration yang baru dibuat di direktori `database/migrations/`.

### 1.3. Implementasikan Stored Procedure

Isi file migration dengan kode berikut:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS log_pembatalan_procedure");

        DB::unprepared("
            CREATE PROCEDURE log_pembatalan_procedure(
                IN p_transaksi_id INT,
                IN p_alasan VARCHAR(255),
                IN p_user_name VARCHAR(255)
            )
            BEGIN
                DECLARE v_existing_count INT DEFAULT 0;

                SELECT COUNT(*)
                INTO v_existing_count
                FROM log_pembatalan
                WHERE transaksi_id = p_transaksi_id;

                IF v_existing_count = 0 THEN
                    INSERT INTO log_pembatalan (
                        transaksi_id,
                        alasan,
                        dibatalkan_oleh,
                        created_at,
                        updated_at
                    )
                    VALUES (
                        p_transaksi_id,
                        p_alasan,
                        p_user_name,
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS log_pembatalan_procedure");
    }
};
```

### 1.4. Jalankan Migration

```php
php artisan migrate
```

### 1.5. Panggil Stored Procedure dari Controller

Buka file app/Http/Controllers/CancelTransactionController.php, kemudian cari method `cancelTransaction()`yang diberi komentar `Bagian 1.5`.

**Ganti seluruh method** `cancelTransaction()` (yang saat ini masih berupa placeholder) dengan kode berikut:

```php
public function cancelTransaction(Request $request, $id)
{
    $request->validate([
        'alasan' =>'required|string|max:255'
    ]);

    try {
        DB::beginTransaction();

        DB::table('transactions')
            ->where('id', $id)
            ->update([
                'status' =>'dibatalkan',
                'updated_at' =>now()
            ]);

        $operatorName =Auth::check()
            ?Auth::user()->name
            :'Kasir Toko';

        DB::statement(
            'CALL log_pembatalan_procedure(?, ?, ?)',
            [
                $id,
                $request->alasan,
                $operatorName
            ]
        );

        DB::commit();

        return redirect()->back()
            ->with(
                'success',
                "Pesanan #{$id} berhasil dibatalkan melalui Stored Procedure."
            );

    } catch (\Exception $e) {
        DB::rollBack();

        return redirect()->back()
            ->with(
                'error',
                'Gagal membatalkan pesanan: ' . $e->getMessage()
            );
    }
}
```

> Import `Request`, `DB`, dan `Auth` sudah tersedia di bagian atas file, jadi tidak perlu ditambahkan lagi.
>

### 1.6. Pengujian

1. Buka aplikasi Laravel.
2. Masuk ke Card **Stored Procedure**.
3. Pilih salah satu pesanan.
4. Masukkan alasan pembatalan.
5. Klik tombol **Batalkan Pesanan**.
6. Periksa tabel `log_pembatalan` melalui aplikasi atau database client.

**Hasil yang diharapkan:** Status pesanan berubah menjadi `dibatalkan` dan riwayat pembatalan dicatat oleh Stored Procedure.

## 2 — Stored Function (Kalkulasi Omzet)

### 2.1. Konsep

Stored Function adalah program SQL yang disimpan di database dan mengembalikan sebuah nilai melalui `RETURN`.

Function dapat dipanggil dari query SQL, misalnya:

```sql
SELECT calculate_today_revenue();
```

Pada praktikum ini, Stored Function digunakan untuk menghitung omzet dan jumlah transaksi berdasarkan periode tertentu.

### 2.2. Buat Migration

Jalankan:

```bash
php artisan make:migration create_revenue_functions
```

Buka file migration yang dihasilkan.

### 2.3. Implementasikan Stored Functions

Salin kode berikut ke file migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up():void
    {
        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_today_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_month_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_total_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_user_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS count_today_transactions"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS count_month_transactions"
        );

        // 1. Omzet hari ini
        DB::unprepared("
            CREATE FUNCTION calculate_today_revenue()
            RETURNS DECIMAL(15,2)
            NOT DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE v_total DECIMAL(15,2) DEFAULT 0.00;

                SELECT COALESCE(SUM(total_harga), 0.00)
                INTO v_total
                FROM transactions
                WHERE tanggal >= CURDATE()
                  AND tanggal < CURDATE() + INTERVAL 1 DAY
                  AND status != 'dibatalkan';

                RETURN v_total;
            END
        ");

        // 2. Omzet bulan ini
        DB::unprepared("
            CREATE FUNCTION calculate_month_revenue()
            RETURNS DECIMAL(15,2)
            NOT DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE v_total DECIMAL(15,2) DEFAULT 0.00;

                SELECT COALESCE(SUM(total_harga), 0.00)
                INTO v_total
                FROM transactions
                WHERE tanggal >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                  AND tanggal < DATE_FORMAT(
                      CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01'
                  )
                  AND status != 'dibatalkan';

                RETURN v_total;
            END
        ");

        // 3. Total seluruh omzet
        DB::unprepared("
            CREATE FUNCTION calculate_total_revenue()
            RETURNS DECIMAL(15,2)
            NOT DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE v_total DECIMAL(15,2) DEFAULT 0.00;

                SELECT COALESCE(SUM(total_harga), 0.00)
                INTO v_total
                FROM transactions
                WHERE status != 'dibatalkan';

                RETURN v_total;
            END
        ");

        // 4. Omzet berdasarkan kasir
        DB::unprepared("
            CREATE FUNCTION calculate_user_revenue(p_user_id INT)
            RETURNS DECIMAL(15,2)
            NOT DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE v_total DECIMAL(15,2) DEFAULT 0.00;

                SELECT COALESCE(SUM(total_harga), 0.00)
                INTO v_total
                FROM transactions
                WHERE user_id = p_user_id
                  AND status != 'dibatalkan';

                RETURN v_total;
            END
        ");

        // 5. Jumlah transaksi hari ini
        DB::unprepared("
            CREATE FUNCTION count_today_transactions()
            RETURNS INT
            NOT DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE v_count INT DEFAULT 0;

                SELECT COUNT(*)
                INTO v_count
                FROM transactions
                WHERE tanggal >= CURDATE()
                  AND tanggal < CURDATE() + INTERVAL 1 DAY
                  AND status != 'dibatalkan';

                RETURN v_count;
            END
        ");

        // 6. Jumlah transaksi bulan ini
        DB::unprepared("
            CREATE FUNCTION count_month_transactions()
            RETURNS INT
            NOT DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE v_count INT DEFAULT 0;

                SELECT COUNT(*)
                INTO v_count
                FROM transactions
                WHERE tanggal >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                  AND tanggal < DATE_FORMAT(
                      CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01'
                  )
                  AND status != 'dibatalkan';

                RETURN v_count;
            END
        ");
    }

    public function down():void
    {
        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_today_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_month_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_total_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS calculate_user_revenue"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS count_today_transactions"
        );

        DB::unprepared(
            "DROP FUNCTION IF EXISTS count_month_transactions"
        );
    }
};
```

> **Catatan:** Function menggunakan `NOT DETERMINISTIC` karena hasilnya bergantung pada isi tabel dan, untuk perhitungan periode, tanggal saat function dipanggil.
>

### 2.4. Jalankan Migration

```bash
php artisan migrate
```

### 2.5. Hubungkan Function dengan Model Laravel

Buka app/Models/Transaction.php. Import `DB` sudah tersedia di bagian atas file.

Cari komentar `Bagian 2.5` di bagian bawah class `Transaction`, lalu tempelkan method berikut di bawah komentar tersebut:

```php
/**
 * Pendapatan hari ini.
 */
public static function getTodayRevenue()
{
    $result =DB::selectOne(
        'SELECT calculate_today_revenue() AS total'
    );

    return $result ?(float) $result->total :0.00;
}

/**
 * Pendapatan bulan ini.
 */
public static function getMonthRevenue()
{
    $result =DB::selectOne(
        'SELECT calculate_month_revenue() AS total'
    );

    return $result ?(float) $result->total :0.00;
}

/**
 * Total seluruh omzet.
 */
public static function getTotalRevenue()
{
    $result =DB::selectOne(
        'SELECT calculate_total_revenue() AS total'
    );

    return $result ?(float) $result->total :0.00;
}

/**
 * Pendapatan kasir tertentu.
 */
public static function getUserRevenue($userId)
{
    $result =DB::selectOne(
        'SELECT calculate_user_revenue(?)AS total',
        [$userId]
    );

    return $result ?(float) $result->total :0.00;
}

/**
 * Jumlah transaksi hari ini.
 */
public static function getTodayTransactionsCount()
{
    $result =DB::selectOne(
        'SELECT count_today_transactions() AS total'
    );

    return $result ?(int) $result->total :0;
}

/**
 * Jumlah transaksi bulan ini.
 */
public static function getMonthTransactionsCount()
{
    $result =DB::selectOne(
        'SELECT count_month_transactions() AS total'
    );

    return $result ?(int) $result->total :0;
}
```

> Method ini dipanggil otomatis oleh `DashboardController` untuk mengisi Card **Database Function**. Controller tidak perlu diubah.
>

### 2.6. Pengujian

1. Refresh aplikasi.
2. Buka Card **Database Function**.
3. Periksa nilai omzet hari ini, omzet bulan ini, total omzet, serta jumlah transaksi.
4. Batalkan satu pesanan melalui card Stored Procedure, lalu periksa kembali. Omzet harus berkurang karena function tidak menghitung transaksi `dibatalkan`.

**Hasil yang diharapkan:** Laravel menampilkan hasil kalkulasi yang diperoleh dengan memanggil Stored Function MySQL.

## 3 — Database Views (Query Virtual Pesanan)

### 3.1. Konsep

Pada praktikum ini, Satu View akan dibuat:

- `v_transaksi_sukses`: menampilkan transaksi selesai beserta informasi customer dan kasir.

### 3.2. Buat Migration

```bash
php artisan make:migration create_database_views
```

### 3.3. Implementasikan Database Views

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up():void
    {
        DB::unprepared("
            CREATE OR REPLACE VIEW v_transaksi_sukses AS
            SELECT
                t.id AS transaksi_id,
                c.nama AS nama_customer,
                c.telepon AS telepon_customer,
                u.name AS nama_kasir,
                t.tanggal,
                t.total_harga,
                t.status,
                t.created_at
            FROM transactions t
            INNER JOIN customers c
                ON t.customer_id = c.id
            INNER JOIN users u
                ON t.user_id = u.id
            WHERE t.status = 'selesai'
              AND t.deleted_at IS NULL
        ");
    }

    public function down():void
    {
        DB::unprepared("DROP VIEW IF EXISTS v_transaksi_sukses");
    }
};
```

Isi file migration dengan kode berikut:

> **Catatan:** Query di atas mengasumsikan tabel `transactions` memiliki kolom `deleted_at`, sedangkan tabel `customers` memiliki kolom sesuai nama yang digunakan. Sesuaikan query apabila struktur database project berbeda.
>

### 3.4. Jalankan Migration

```bash
php artisan migrate
```

### 3.5. Pengujian

View dapat diperiksa langsung melalui MySQL:

```sql
SELECT * FROM v_transaksi_sukses;
```

Di Laravel, View dapat diakses seperti tabel biasa:

```php
$transaksi =DB::table('v_transaksi_sukses')->get();
```

> Query `v_transaksi_sukses` sudah dipanggil di `DashboardController`, jadi tidak ada kode controller yang perlu ditambahkan.
>

Periksa Card **Database View** pada aplikasi.

**Hasil yang diharapkan:** Daftar transaksi sukses dapat ditampilkan dari View tanpa menulis ulang query gabungan pada setiap pemanggilan.

## 4 — Database Triggers (Audit Log Otomatis)

### 4.1. Konsep

Pada praktikum ini, Trigger digunakan untuk mencatat aktivitas pembuatan transaksi dan perubahan status transaksi ke tabel `log_aktivitas`.

### 4.2. Buat Migration

```bash
php artisan make:migration create_database_triggers
```

### 4.3. Implementasikan Trigger

Isi file migration dengan kode berikut:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up():void
    {
        DB::unprepared(
            "DROP TRIGGER IF EXISTS trg_after_transaksi_insert"
        );

        DB::unprepared(
            "DROP TRIGGER IF EXISTS trg_after_transaksi_update"
        );

        // 1. Trigger setelah transaksi baru dibuat
        DB::unprepared("
            CREATE TRIGGER trg_after_transaksi_insert
            AFTER INSERT ON transactions
            FOR EACH ROW
            BEGIN
                INSERT INTO log_aktivitas (
                    transaksi_id,
                    aksi,
                    keterangan,
                    created_at
                )
                VALUES (
                    NEW.id,
                    'PESANAN_BARU',
                    CONCAT(
                        'Pesanan baru ID #',
                        NEW.id,
                        ' dibuat dengan total Rp ',
                        FORMAT(NEW.total_harga, 0),
                        ' (Status: ',
                        NEW.status,
                        ')'
                    ),
                    NOW()
                );
            END
        ");

        // 2. Trigger setelah status transaksi berubah
        DB::unprepared("
            CREATE TRIGGER trg_after_transaksi_update
            AFTER UPDATE ON transactions
            FOR EACH ROW
            BEGIN
                IF OLD.status <> NEW.status THEN
                    INSERT INTO log_aktivitas (
                        transaksi_id,
                        aksi,
                        keterangan,
                        created_at
                    )
                    VALUES (
                        NEW.id,
                        'STATUS_PESANAN_BERUBAH',
                        CONCAT(
                            'Status pesanan ID #',
                            NEW.id,
                            ' berubah dari [',
                            OLD.status,
                            '] menjadi [',
                            NEW.status,
                            ']'
                        ),
                        NOW()
                    );
                END IF;
            END
        ");
    }

    public function down():void
    {
        DB::unprepared(
            "DROP TRIGGER IF EXISTS trg_after_transaksi_insert"
        );

        DB::unprepared(
            "DROP TRIGGER IF EXISTS trg_after_transaksi_update"
        );
    }
};
```

### 4.4. Jalankan Migration

```bash
php artisan migrate
```

### 4.5. Pengujian

1. Batalkan satu pesanan melalui card **Stored Procedure**.
2. Periksa `log_aktivitas`. Akan muncul `STATUS_PESANAN_BERUBAH`, padahal kode pembatalan tidak pernah menulis ke tabel itu.
3. Setelah Modul 5 selesai, buat pesanan baru dan periksa lagi. Akan muncul `PESANAN_BARU`.

Untuk memeriksa hasil secara langsung:

```sql
SELECT *
FROM log_aktivitas
ORDER BY created_at DESC;
```

**Hasil yang diharapkan:** Catatan aktivitas ditambahkan secara otomatis oleh MySQL tanpa perlu menulis perintah pencatatan log pada setiap proses di controller.

## 5 — Database Transactions (ACID: COMMIT dan ROLLBACK)

### 5.1. Konsep

Pada prinsip Atomicity dalam ACID, seluruh operasi dalam sebuah transaksi harus berhasil bersama atau dibatalkan bersama.

- `DB::beginTransaction()`: memulai transaksi.
- `DB::commit()`: mengesahkan perubahan.
- `DB::rollBack()`: membatalkan perubahan yang belum di-commit.

Pada praktikum ini, penyimpanan pesanan melibatkan tabel `transactions` dan `transaction_details`. Keduanya harus konsisten.

### 5.2. Terapkan Transaction pada Controller

Buka app/Http/Controllers/TransactionController.php, cari method `storeTransaction()` yang diberi komentar `Bagian 5.2`.

**Ganti seluruh method** `storeTransaction()` (yang saat ini masih berupa placeholder) dengan kode berikut:

```php
public function storeTransaction(Request $request)
{
    $request->validate([
        'customer_id' =>'required|exists:customers,id',
        'product_id'  =>'required|exists:products,id',
        'qty'         =>'required|integer|min:1',
    ]);

    $product =Product::findOrFail($request->product_id);

    $qty =(int) $request->qty;
    $subtotal =$product->harga *$qty;
    $totalHarga =$subtotal;

    $userId =Auth::id() ??(User::first()->id ??1);

    try {
        DB::beginTransaction();

        // 1. Simpan transaksi utama
        $transactionId =DB::table('transactions')
            ->insertGetId([
                'customer_id' =>$request->customer_id,
                'user_id'     =>$userId,
                'tanggal'     =>now(),
                'total_harga' =>$totalHarga,
                'status'      =>'selesai',
                'created_at'  =>now(),
                'updated_at'  =>now(),
            ]);

        // 2. Simpan detail transaksi
        DB::table('transaction_details')->insert([
            'transaction_id' =>$transactionId,
            'product_id'     =>$product->id,
            'harga'          =>$product->harga,
            'qty'            =>$qty,
            'subtotal'       =>$subtotal,
            'created_at'     =>now(),
            'updated_at'     =>now(),
        ]);

        // 3. Simulasi error untuk menguji ROLLBACK
        if ($request->input('simulate_rollback') =='1') {
            throw new \Exception(
                'Simulasi error: transaksi sengaja dibatalkan.'
            );
        }

        // 4. Simpan seluruh perubahan
        DB::commit();

        return redirect()->back()->with(
            'success',
            "Pesanan #{$transactionId} berhasil disimpan."
        );

    } catch (\Throwable $e) {
        DB::rollBack();

        return redirect()->back()->with(
            'error',
            'Transaksi dibatalkan: ' . $e->getMessage()
        );
    }
}
```

> Import `Request`, `DB`, `Auth`, `Product`, dan `User` sudah tersedia di bagian atas file, jadi tidak perlu ditambahkan lagi.
>

> **Catatan:** Implementasi ini merupakan contoh dasar untuk pembelajaran. Pada aplikasi yang digunakan secara nyata, stok produk, validasi jumlah pembelian, dan akses pengguna perlu divalidasi secara tepat. Untuk menghindari pembacaan data sebelum transaksi yang tidak konsisten, data produk dapat diperiksa ulang di dalam transaksi.
>

### 5.3. Pengujian COMMIT

1. Buka form pembuatan pesanan.
2. Pilih customer dan produk.
3. Masukkan jumlah pembelian.
4. Pastikan opsi simulasi rollback **tidak** dicentang.
5. Klik tombol proses pesanan.
6. Periksa tabel `transactions` dan `transaction_details`.

**Hasil yang diharapkan:** Data transaksi utama dan detail transaksi tersimpan.

### 5.4. Pengujian ROLLBACK

1. Isi form pesanan seperti sebelumnya.
2. Centang opsi **Uji Rollback** .
3. Klik tombol proses pesanan.
4. Periksa kembali tabel `transactions` dan `transaction_details`.

**Hasil yang diharapkan:** Data transaksi yang dibuat dalam proses tersebut tidak tersimpan karena operasi dibatalkan menggunakan `DB::rollBack()`.

Jika Trigger pada Materi 4 aktif, catatan log yang dibuat oleh Trigger dalam transaksi yang sama juga akan dibatalkan selama tabel menggunakan engine transaksional, seperti InnoDB.

## 6 — User Management dan Dynamic Database Connection

### 6.0. Konsep

**Ada dua jenis "user" yang berbeda:**

|  | **User Aplikasi** | **User MySQL** |
| --- | --- | --- |
| Disimpan di | Tabel `users` (kolom `role`) | Sistem MySQL (`CREATE USER`) |
| Dipakai untuk | Login ke halaman web | Koneksi Laravel ke MySQL |
| Contoh | Admin, Kasir | `kasir@localhost`, `admin@localhost` |
| Dijaga oleh | Middleware `admin` / `kasir` | `GRANT` |

**Jadi masalah sebelum bagian ini diterapkan:** siapa pun yang login ke web, Laravel tetap terhubung ke MySQL sebagai **root**. Artinya kasir di web sebenarnya punya kekuasaan penuh di database.

**Solusi:** buat user MySQL terpisah untuk kasir dan admin, lalu Laravel **berganti koneksi** sesuai role yang login. Dengan begitu ada dua lapis pengamanan:

1. **Lapis aplikasi:** middleware menolak halaman yang bukan haknya.
2. **Lapis database:** MySQL menolak query yang bukan haknya, walaupun ada bug di aplikasi.

**Prinsip *least privilege*:** setiap user hanya diberi hak seminimal yang ia butuhkan.

**Alur sebuah request:**

```
Login → StartSession → DBDynamicConnection (pilih mysql_kasir / mysql_admin)
      → Middleware role → Controller → MySQL memeriksa GRANT
```

### 6.1. Konfigurasi `.env`

Buka file `.env`, lalu tambahkan baris berikut **di bawah** `DB_PASSWORD=`:

```php
DB_USERNAME_KASIR=kasir
DB_PASSWORD_KASIR=kasir
DB_USERNAME_ADMIN=admin
DB_PASSWORD_ADMIN=admin
```

**Kenapa di `.env`?** Username dan password adalah data **rahasia** dan bisa berbeda di setiap komputer. File `.env` tidak ikut diunggah ke Git, sehingga rahasia tidak tersebar.

### 6.2. Konfigurasi config/database.php

Buka config/database.php, lalu cari komentar cari komentar `Bagian 6.2`. Tempelkan dua koneksi berikut **di bawah komentar tersebut**, sejajar dengan koneksi `mysql`:

```php
'mysql_kasir' =>[
    'driver' =>'mysql',
    'host' =>env('DB_HOST', '127.0.0.1'),
    'port' =>env('DB_PORT', '3306'),
    'database' =>env('DB_DATABASE', 'msbd-lab'),
    'username' =>env('DB_USERNAME_KASIR', 'kasir'),
    'password' =>env('DB_PASSWORD_KASIR', 'kasir'),
    'unix_socket' =>env('DB_SOCKET', ''),
    'charset' =>'utf8mb4',
    'collation' =>'utf8mb4_unicode_ci',
    'prefix' =>'',
    'prefix_indexes' =>true,
    'strict' =>true,
    'engine' =>null,
    'options' =>extension_loaded('pdo_mysql') ?array_filter([
        Mysql::ATTR_SSL_CA =>env('MYSQL_ATTR_SSL_CA'),
    ]) :[],
],

'mysql_admin' =>[
    'driver' =>'mysql',
    'host' =>env('DB_HOST', '127.0.0.1'),
    'port' =>env('DB_PORT', '3306'),
    'database' =>env('DB_DATABASE', 'msbd-lab'),
    'username' =>env('DB_USERNAME_ADMIN', 'admin'),
    'password' =>env('DB_PASSWORD_ADMIN', 'admin'),
    'unix_socket' =>env('DB_SOCKET', ''),
    'charset' =>'utf8mb4',
    'collation' =>'utf8mb4_unicode_ci',
    'prefix' =>'',
    'prefix_indexes' =>true,
    'strict' =>true,
    'engine' =>null,
    'options' =>extension_loaded('pdo_mysql') ?array_filter([
        Mysql::ATTR_SSL_CA =>env('MYSQL_ATTR_SSL_CA'),
    ]) :[],
],
```

**Penjelasan:**

Isinya sama dengan koneksi `mysql`, hanya `username` dan `password` yang diambil dari `.env` Bagian 6.1. Laravel boleh punya banyak koneksi database, dan file ini tempat mendaftarkannya. `Mysql::ATTR_SSL_CA` sama dengan yang dipakai koneksi `mysql` di file ini (`use Pdo\Mysql;` sudah ada di baris paling atas).

### 6.3. Middleware `DBDynamicConnection`

Middleware ini **sudah tersedia** di app/Http/Middleware/DBDynamicConnection.php (penanda
(komentar `Bagian 6.3`). **Tidak perlu diubah**, cukup dipahami:

```php
class DBDynamicConnection
{
    public function handle(Request $request, Closure $next):Response
    {
        if (Auth::check()) {
            $user =Auth::user();

            if (!in_array($user->role, ['kasir', 'admin'])) {
                return $next($request);
            }

            $targetConnection =$user->role ==='kasir' ?'mysql_kasir' :'mysql_admin';

            if ($targetConnection &&Config::has("database.connections.{$targetConnection}")) {
                try {
                    DB::connection($targetConnection)->getPdo();

                    Config::set('database.default', $targetConnection);
                    DB::purge($targetConnection);
                    DB::setDefaultConnection($targetConnection);
                } catch (\Throwable $e) {
                    Config::set('database.default', 'mysql');
                    DB::setDefaultConnection('mysql');
                }
            }
        }

        return $next($request);
    }
}
```

**Cara kerjanya:**

1. `Auth::check()`: hanya berlaku untuk pengguna yang sudah login. Tamu tetap memakai koneksi `mysql`.
2. Role `kasir` memakai `mysql_kasir`, role `admin` memakai `mysql_admin`.
3. `Config::has(...)`: jika Bagian 6.2 belum dikerjakan, koneksi tidak diganti, jadi aplikasi tidak error.
4. `getPdo()`: mencoba login ke MySQL dengan user tersebut.
5. `setDefaultConnection(...)`: sejak titik ini **semua query** di controller dan model otomatis memakai user MySQL tersebut, tanpa mengubah satu controller pun.
6. `catch`: jika login MySQL gagal (misalnya Bagian 6.5 belum dijalankan), aplikasi kembali ke koneksi `mysql`.

**Kenapa memakai middleware?** Pemilihan koneksi harus terjadi **di setiap request, sebelum query apa pun dijalankan**. Middleware adalah tempat untuk urusan yang berlaku di semua halaman seperti ini.

### 6.4. Pendaftaran Middleware

Middleware **sudah terdaftar** di **bootstrap/app.php** (komentar `Bagian 6.4`):

```php
->withMiddleware(function (Middleware $middleware):void {
    $middleware->web(append: [
        \App\Http\Middleware\DBDynamicConnection::class,
    ]);

    $middleware->alias([
        'admin' =>\App\Http\Middleware\AdminMiddleware::class,
        'kasir' =>\App\Http\Middleware\KasirMiddleware::class,
        'role'  =>\App\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

- `append` membuat `DBDynamicConnection` berjalan **paling akhir** di grup `web`, yaitu **setelah** session dimuat. Jika dijalankan lebih awal, `Auth::check()` belum tahu siapa yang login.
- Alias `admin` dan `kasir` adalah lapis pengamanan **aplikasi** yang dipakai di routes/web.php.

### 6.5. Buat Migration User MySQL dan GRANT

```php
php artisan make:migration setup_database_users_and_grants
```

Ganti seluruh isi file migration dengan kode berikut:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $db = config('database.connections.mysql.database');

        $kasir     = env('DB_USERNAME_KASIR', 'kasir');
        $kasirPass = env('DB_PASSWORD_KASIR', 'kasir');
        $admin     = env('DB_USERNAME_ADMIN', 'admin');
        $adminPass = env('DB_PASSWORD_ADMIN', 'admin');

        // 1. Membuat user MySQL
        DB::unprepared("CREATE USER IF NOT EXISTS '{$kasir}'@'localhost' IDENTIFIED BY '{$kasirPass}'");
        DB::unprepared("CREATE USER IF NOT EXISTS '{$admin}'@'localhost' IDENTIFIED BY '{$adminPass}'");

        // 2. Hak akses kasir: diberikan per tabel (least privilege)
        $kasirGrants = [
            'customers'           => 'SELECT',
            'products'            => 'SELECT',
            'users'               => 'SELECT',
            'transactions'        => 'SELECT, INSERT, UPDATE',
            'transaction_details' => 'SELECT, INSERT',
            'log_pembatalan'      => 'SELECT',
            'log_aktivitas'       => 'SELECT',
            'v_transaksi_sukses'  => 'SELECT',
            'sessions'            => 'SELECT, INSERT, UPDATE, DELETE',
        ];

        foreach ($kasirGrants as $table => $privileges) {
            DB::unprepared("GRANT {$privileges} ON `{$db}`.`{$table}` TO '{$kasir}'@'localhost'");
        }

        // Izin menjalankan Stored Procedure & Stored Function
        DB::unprepared("GRANT EXECUTE ON `{$db}`.* TO '{$kasir}'@'localhost'");

        // 3. Hak akses admin: penuh, tetapi hanya pada database praktikum
        DB::unprepared("GRANT ALL PRIVILEGES ON `{$db}`.* TO '{$admin}'@'localhost'");
    }

    public function down(): void
    {
        $kasir = env('DB_USERNAME_KASIR', 'kasir');
        $admin = env('DB_USERNAME_ADMIN', 'admin');

        DB::unprepared("DROP USER IF EXISTS '{$kasir}'@'localhost'");
        DB::unprepared("DROP USER IF EXISTS '{$admin}'@'localhost'");
    }
};
```

**Hak akses yang dihasilkan:**

| **Tabel / Objek** | **Kasir** | **Admin** |
| --- | --- | --- |
| `customers`, `products`, `users` | SELECT saja | ALL |
| `transactions` | SELECT, INSERT, UPDATE | ALL |
| `transaction_details` | SELECT, INSERT | ALL |
| `log_pembatalan`, `log_aktivitas`, `v_transaksi_sukses` | SELECT saja | ALL |
| Procedure & Function | EXECUTE | ALL |

### 6.6. Jalankan Migration

```
php artisan migrate
```

### 6.7. Route Pengecekan Koneksi

Route dan method ini **sudah tersedia**, tidak perlu menambahkan kode.

Route di routes/web.php:

```php
Route::get('/check-connection', [UserManagementController::class, 'checkConnection'])
    ->name('check.connection');
```

Method di app/Http/Controllers/UserManagementController.php:

```php
public function checkConnection()
{
    return response()->json([
        'logged_in_as'     =>Auth::user()->name ??'guest',
        'role'             =>Auth::user()->role ??'none',
        'using_connection' =>DB::getDefaultConnection(),
        'mysql_user'       =>$this->currentMysqlUser(),
        'database'         =>DB::connection()->getDatabaseName(),
    ]);
}
```

- `using_connection`: nama koneksi yang dipakai **Laravel**.
- `mysql_user`: hasil `SELECT CURRENT_USER()`, yaitu user yang dikenali **MySQL**. Inilah bukti bahwa MySQL benar-benar menerima koneksi sebagai kasir/admin.

### 6.8. Pengujian

**A. Cek koneksi**

1. Login sebagai **admin**, lalu buka `/check-connection`. Pastikan `using_connection` = `mysql_admin` dan `mysql_user` = `admin@localhost`.
2. Logout, login sebagai **kasir**, lalu buka `/check-connection` lagi. Pastikan `using_connection` = `mysql_kasir` dan `mysql_user` = `kasir@localhost`
