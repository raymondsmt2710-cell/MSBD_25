# Fitur Pemilihan Suara Elektronik (e-Voting) — Rencana Implementasi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun fitur pemilihan suara elektronik (admin/panitia mengelola, siswa memilih sekali, hasil terbuka setelah selesai) dengan penjagaan keabsahan dan kerahasiaan suara di level MySQL.

**Architecture:** Seluruh aturan pemungutan berada di Stored Procedure `sp_rekam_suara`; controller hanya `CALL` di dalam `DB::beginTransaction()`. Suara bersifat *immutable* lewat trigger, dan `v_hasil_pemilihan` menyembunyikan angka bila status belum `selesai`. Lapis aplikasi memakai middleware `role` + `PemilihanPolicy`.

**Tech Stack:** Laravel 12, PHP 8.2+, MySQL 8.4 (InnoDB), Blade + Tailwind, PHPUnit (MySQL test DB).

**Spec:** `docs/superpowers/specs/2026-11-17-voting-design.md`

## Global Constraints

- Bahasa antarmuka: Indonesia.
- Tanpa dependency Composer baru.
- Tidak mengubah struktur tabel `pemilihan`, `panitia_pemilihan`, `daftar_pemilih`, `kandidat`, `suara`.
- Tidak menyentuh `.env`. Tidak `git push` / `git pull`.
- Palet: teal `#09656F`, aksen oranye `#C97A14`, kartu `rounded-2xl`, font Figtree.
- Siswa **tidak** mendapat `INSERT` ke `suara` maupun `UPDATE` ke `daftar_pemilih`.
- Nama status: `draf`, `berlangsung`, `selesai`.
- Semua `p_status` procedure kelola memakai nilai: `BERHASIL`, `TIDAK_BERHAK`, `STATUS_TIDAK_VALID`, `TIDAK_DITEMUKAN`, `SUDAH_ADA_SUARA`, `BELUM_ADA_KANDIDAT`, `BELUM_ADA_PEMILIH`, `WAKTU_TIDAK_VALID`.
- Semua `p_status` `sp_rekam_suara`: `BERHASIL`, `PEMILIHAN_TIDAK_VALID`, `BELUM_DIMULAI`, `SUDAH_DITUTUP`, `BUKAN_PEMILIH`, `SUDAH_MEMILIH`, `KANDIDAT_TIDAK_VALID`.

## Review Focus

Input/keadaan yang tidak diuji langsung oleh "happy path", tetapi paling mungkin menggigit pengguna:

1. **Dua tab / dobel klik pada detik yang sama** — harus tetap tepat satu suara; yang kedua mendapat `SUDAH_MEMILIH`.
2. **Menghapus kandidat yang sudah punya suara** — suara tidak boleh ikut hilang diam-diam lewat `cascadeOnDelete`.
3. **Menghapus pemilih yang sudah memilih** — jumlah suara tidak boleh melebihi jumlah `sudah_memilih`.
4. **Siswa membaca hitungan sementara langsung dari database** — `jumlah_suara` harus `NULL` selama belum `selesai`.
5. **Membuka pemilihan tanpa kandidat atau tanpa pemilih** — harus ditolak, karena hasilnya tidak bermakna.
6. **Batas waktu tepat di `waktu_selesai`** — suara pada detik itu harus ditolak.
7. **Foto kandidat berupa file bukan gambar atau terlalu besar** — harus ditolak dengan pesan yang jelas.

Pemetaan Review Focus → test yang mengunci:

| # | Test |
| --- | --- |
| 1 | `RekamSuaraTest::test_suara_kedua_ditolak`, `SiswaPemilihanTest::test_siswa_tidak_bisa_mengirim_suara_dua_kali_lewat_http` |
| 2 | `PemilihanTriggerTest::test_kandidat_bersuara_tidak_bisa_dihapus` |
| 3 | `PemilihanTriggerTest::test_pemilih_yang_sudah_memilih_tidak_bisa_dihapus`, `PemilihTest::test_pemilih_yang_sudah_memilih_tidak_bisa_dihapus_lewat_http` |
| 4 | `PemilihanViewTest::test_hasil_pemilihan_null_selama_berlangsung`, `GrantTest::test_siswa_menerima_null_untuk_hitungan_sementara` |
| 5 | `KelolaProcedureTest::test_buka_ditolak_tanpa_kandidat`, `test_buka_ditolak_tanpa_pemilih` |
| 6 | `RekamSuaraTest::test_ditolak_di_luar_rentang_waktu` |
| 7 | `KandidatTest::test_file_bukan_gambar_ditolak` |

---

### Task 1: Infrastruktur test MySQL

**Files:**
- Modify: `phpunit.xml`
- Delete: `_probe.php`, `phpunit.mysql.xml`, `tests/Feature/ProbeTest.php` (berkas sementara dari eksplorasi)
- Create: `tests/Feature/Voting/VotingTestSupport.php`

**Interfaces:**
- Produces: trait `VotingTestSupport` di `tests/Feature/Voting/VotingTestSupport.php` dengan dua method, keduanya mengembalikan array asosiatif yang bentuknya **sama**:

```
[
  'id_pemilihan'    => int,
  'id_kandidat'     => int[],      // 2 kandidat, nomor urut 1 dan 2
  'id_siswa'        => int[],      // 2 siswa aktif di kelas yang sama
  'id_siswa_user'   => int[],      // id_user milik tiap siswa (indeks sama dengan id_siswa)
  'id_admin'        => int,        // id_user role admin
  'id_panitia_user' => int,        // id_user role guru, BELUM terdaftar di panitia_pemilihan
  'id_guru_lain'    => int,        // id_user role guru lain, tidak terdaftar sebagai panitia
  'id_kelas'        => int,
  'id_organisasi'   => int,
  'id_tahun_ajaran' => int,
]
```

Trait juga menyediakan `userDari(int $idUser): \App\Models\User` yang mengembalikan instance `User` (dipakai `actingAs`).

- `seedPemilihanBerlangsung()` → status `berlangsung`, `waktu_mulai = NOW() - INTERVAL 1 HOUR`, `waktu_selesai = NOW() + INTERVAL 1 HOUR`, 2 kandidat, 2 baris `daftar_pemilih` (`sudah_memilih = 0`).
- `seedPemilihanDraf()` → status `draf`, `waktu_mulai = NOW() + INTERVAL 1 HOUR`, `waktu_selesai = NOW() + INTERVAL 1 DAY`, 2 kandidat, 2 baris `daftar_pemilih`.
- Trait memakai `DB::table()->insertGetId()` langsung, bukan model, agar test database tidak bergantung pada Task 9. Setiap method memanggil `RefreshDatabase` dari kelas test-nya.

- [ ] **Step 1: Ubah `phpunit.xml` agar memakai MySQL**

Ganti blok `<php>` menjadi: `APP_ENV=testing`, `BCRYPT_ROUNDS=4`, `CACHE_STORE=array`, `DB_CONNECTION=mysql`, `DB_DATABASE=msbd_25_test`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`. Hapus `DB_URL` dan baris sqlite. Alasan: fitur ini bergantung pada procedure/view/trigger MySQL, sehingga sqlite tidak bisa menguji apa pun yang berarti.

- [ ] **Step 2: Buat database uji dan migrasikan sekali**

```bash
php artisan db:create   # atau CREATE DATABASE msbd_25_test ...
$env:DB_DATABASE="msbd_25_test"; php artisan migrate
```

Expected: database `msbd_25_test` berisi seluruh tabel + procedure/view/trigger. **Jangan** memakai nama `msbd_25` di langkah ini.

- [ ] **Step 3: Tulis trait bantuan test**

Trait `VotingTestSupport` (detail di blok Interfaces). Kelas test memakai `Illuminate\Foundation\Testing\DatabaseTruncation`, **bukan** `RefreshDatabase`.

Alasannya sudah diuji langsung di proyek ini: middleware `DBDynamicConnection` mengganti koneksi default ke `mysql_siswa`/`mysql_admin`, sehingga transaksi `RefreshDatabase`/`DatabaseTransactions` (yang dibuka pada koneksi `mysql`) **tidak terlihat** oleh koneksi role — data yang dibuat test menjadi tak terlihat dari dalam request. `DatabaseTruncation` mengosongkan tabel setelah setiap test dan datanya terlihat lintas koneksi. Biaya per test ≈0,3 detik (tanpa menjalankan ulang 33 migration), jadi tetap jauh lebih murah daripada `RefreshDatabase` yang memakan ≈4,5 detik per test.

Tambahkan juga properti berikut di setiap kelas test fitur voting:

```php
protected array $tablesToTruncate = [
    'suara', 'daftar_pemilih', 'panitia_pemilihan', 'kandidat', 'pemilihan',
    'log_pemilihan', 'anggota_kelas', 'siswa', 'kelas', 'organisasi',
    'tahun_ajaran', 'guru', 'users',
];
```

- [ ] **Step 4: Tulis test pembuktian harness**

`tests/Feature/Voting/HarnessTest.php`:

```php
public function test_procedure_dari_migration_ada(): void
{
    $names = array_map(fn ($r) => $r->Name, DB::select('SHOW PROCEDURE STATUS WHERE Db = DATABASE()'));
    $this->assertContains('sp_scan_presensi', $names);
}

public function test_seed_pemilihan_berlangsung_menghasilkan_data(): void
{
    $data = $this->seedPemilihanBerlangsung();
    $this->assertSame('berlangsung', DB::table('pemilihan')->where('id_pemilihan', $data['id_pemilihan'])->value('status'));
    $this->assertCount(2, $data['id_kandidat']);
}
```

- [ ] **Step 5: Jalankan test**

Run: `php artisan test --filter=HarnessTest`
Expected: PASS 2 tests.

- [ ] **Step 6: Jalankan test fitur presensi yang sudah ada**

Run: `php artisan test --filter=ExampleTest`
Expected: PASS. Ini memastikan perubahan `phpunit.xml` tidak merusak test lama.

- [ ] **Step 7: Hapus berkas sementara & commit**

Hapus `_probe.php`, `phpunit.mysql.xml`, `tests/Feature/ProbeTest.php` bila masih ada.

```bash
git add phpunit.xml tests/Feature/Voting
git commit -m "test: siapkan harness test MySQL untuk fitur pemilihan"
```

---

### Task 2: Tabel `log_pemilihan` dan index

**Files:**
- Create: `database/migrations/2026_11_17_000001_create_log_pemilihan_table.php`
- Create: `database/migrations/2026_11_17_000002_add_pemilihan_indexes.php`
- Test: `tests/Feature/Voting/LogPemilihanSchemaTest.php`

**Interfaces:**
- Produces: tabel `log_pemilihan(id_log, id_pemilihan, id_user NULL, aksi, status_lama NULL, status_baru NULL, keterangan NULL, dibuat_pada)`; index `idx_daftar_pemilih_status` pada `daftar_pemilih (id_pemilihan, sudah_memilih)`, `idx_suara_pemilihan_kandidat` pada `suara (id_pemilihan, id_kandidat)`, `idx_pemilihan_status_waktu` pada `pemilihan (status, waktu_mulai, waktu_selesai)`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_tabel_log_pemilihan_punya_kolom_yang_dibutuhkan(): void
{
    $this->assertTrue(Schema::hasTable('log_pemilihan'));
    foreach (['id_log', 'id_pemilihan', 'id_user', 'aksi', 'status_lama', 'status_baru', 'keterangan', 'dibuat_pada'] as $kolom) {
        $this->assertTrue(Schema::hasColumn('log_pemilihan', $kolom), "kolom {$kolom} tidak ada");
    }
}

public function test_index_pemilihan_ada(): void
{
    $index = collect(DB::select('SHOW INDEX FROM daftar_pemilih'))->pluck('Key_name');
    $this->assertContains('idx_daftar_pemilih_status', $index);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=LogPemilihanSchemaTest`
Expected: FAIL — tabel belum ada.

- [ ] **Step 3: Buat kedua migration**

`log_pemilihan` mengikuti gaya `log_presensi` (tanpa foreign key agar log tetap ada walau data asal dihapus), dengan `index('id_pemilihan')` dan kolom `status_lama`/`status_baru` nullable. Migration index memakai `Schema::table()` dan menyebut nama index secara eksplisit; `down()` menghapusnya.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=LogPemilihanSchemaTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations tests/Feature/Voting/LogPemilihanSchemaTest.php
git commit -m "feat: tabel log pemilihan dan index"
```

---

### Task 3: Stored Function pemilihan

**Files:**
- Create: `database/migrations/2026_11_17_000003_create_pemilihan_functions.php`
- Test: `tests/Feature/Voting/PemilihanFunctionTest.php`

**Interfaces:**
- Produces: `fn_sudah_memilih(p_id_pemilihan INT, p_id_siswa INT) -> TINYINT(1)`, `fn_boleh_memilih(p_id_pemilihan INT, p_id_siswa INT) -> TINYINT(1)`, `fn_total_suara(p_id_pemilihan INT) -> INT`, `fn_partisipasi(p_id_pemilihan INT) -> DECIMAL(5,2)`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_total_suara_dan_partisipasi_awal_nol(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->assertSame(0, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
    $this->assertSame('0.00', DB::selectOne('SELECT fn_partisipasi(?) p', [$d['id_pemilihan']])->p);
}

public function test_boleh_memilih_benar_untuk_pemilih_yang_belum_memilih(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->assertSame(1, (int) DB::selectOne('SELECT fn_boleh_memilih(?,?) b', [$d['id_pemilihan'], $d['id_siswa'][0]])->b);
    $this->assertSame(0, (int) DB::selectOne('SELECT fn_sudah_memilih(?,?) b', [$d['id_pemilihan'], $d['id_siswa'][0]])->b);
}

public function test_boleh_memilih_salah_untuk_siswa_yang_tidak_terdaftar(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->assertSame(0, (int) DB::selectOne('SELECT fn_boleh_memilih(?,?) b', [$d['id_pemilihan'], 999999])->b);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=PemilihanFunctionTest`
Expected: FAIL — function belum ada.

- [ ] **Step 3: Buat migration function**

Ikuti pola `000029_create_presensi_functions.php`: guard `mysql()`/`mariadb()`, `DROP FUNCTION IF EXISTS` sebelum `CREATE`. Semua function `NOT DETERMINISTIC READS SQL DATA`. `fn_boleh_memilih` mengembalikan 1 hanya bila: status `berlangsung`, `NOW() BETWEEN waktu_mulai AND waktu_selesai`, siswa ada di `daftar_pemilih`, dan `sudah_memilih = 0`. `fn_partisipasi` memakai `COALESCE` dan mengembalikan `0.00` bila total pemilih 0 (hindari pembagian nol).

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=PemilihanFunctionTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations tests/Feature/Voting/PemilihanFunctionTest.php
git commit -m "feat: stored function pemilihan"
```

---

### Task 4: View pemilihan (termasuk penyembunyian hasil)

**Files:**
- Create: `database/migrations/2026_11_17_000004_create_pemilihan_views.php`
- Test: `tests/Feature/Voting/PemilihanViewTest.php`

**Interfaces:**
- Produces: `v_kandidat_pemilihan` (tanpa `jumlah_suara`), `v_kandidat_pemilihan_hasil` (+ `jumlah_suara`, `persentase`), `v_hasil_pemilihan` (`jumlah_suara`/`persentase` **NULL** bila status ≠ `selesai`), `v_partisipasi_pemilihan`, `v_pemilihan_pengelola`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_hasil_pemilihan_null_selama_berlangsung(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $baris = DB::table('v_hasil_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->get();
    $this->assertCount(2, $baris);
    $this->assertNull($baris[0]->jumlah_suara);
    $this->assertNull($baris[0]->persentase);
}

public function test_hasil_pemilihan_terlihat_setelah_selesai(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);
    $baris = DB::table('v_hasil_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->get();
    $this->assertSame(0, (int) $baris[0]->jumlah_suara);
}

public function test_kandidat_view_tidak_memuat_kolom_suara(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $kolom = array_keys((array) DB::table('v_kandidat_pemilihan')->first());
    $this->assertNotContains('jumlah_suara', $kolom);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=PemilihanViewTest`
Expected: FAIL — view belum ada.

- [ ] **Step 3: Buat migration view**

Ikuti pola `000030_create_presensi_views.php` (`CREATE OR REPLACE VIEW`). Untuk `v_hasil_pemilihan`, bungkus hitungan:

```sql
CASE WHEN p.status = 'selesai' THEN (SELECT COUNT(*) FROM suara s WHERE s.id_kandidat = k.id_kandidat)
     ELSE NULL END AS jumlah_suara
```

dan persentase dihitung dari nilai itu (NULL bila belum selesai). Penanda pemenang memakai `jumlah_suara = MAX(jumlah_suara)` per pemilihan, dan ikut NULL bila belum selesai.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=PemilihanViewTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations tests/Feature/Voting/PemilihanViewTest.php
git commit -m "feat: view pemilihan dengan penyembunyian hasil"
```

---

### Task 5: Trigger penjaga integritas dan audit

**Files:**
- Create: `database/migrations/2026_11_17_000005_create_pemilihan_triggers.php`
- Test: `tests/Feature/Voting/PemilihanTriggerTest.php`

**Interfaces:**
- Produces: `trg_before_suara_insert`, `trg_before_suara_update`, `trg_before_suara_delete`, `trg_before_kandidat_delete`, `trg_before_daftar_pemilih_delete`, `trg_before_pemilihan_update`, `trg_after_pemilihan_update`, `trg_after_daftar_pemilih_update`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_suara_tidak_bisa_diubah(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('suara')->insert(['id_suara' => Str::uuid()->toString(), 'id_pemilihan' => $d['id_pemilihan'], 'id_kandidat' => $d['id_kandidat'][0]]);
    $this->expectException(\Throwable::class);
    DB::table('suara')->where('id_pemilihan', $d['id_pemilihan'])->update(['id_kandidat' => $d['id_kandidat'][1]]);
}

public function test_suara_tidak_bisa_dihapus(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('suara')->insert(['id_suara' => Str::uuid()->toString(), 'id_pemilihan' => $d['id_pemilihan'], 'id_kandidat' => $d['id_kandidat'][0]]);
    $this->expectException(\Throwable::class);
    DB::table('suara')->where('id_pemilihan', $d['id_pemilihan'])->delete();
}

public function test_kandidat_bersuara_tidak_bisa_dihapus(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('suara')->insert(['id_suara' => Str::uuid()->toString(), 'id_pemilihan' => $d['id_pemilihan'], 'id_kandidat' => $d['id_kandidat'][0]]);
    $this->expectException(\Throwable::class);
    DB::table('kandidat')->where('id_kandidat', $d['id_kandidat'][0])->delete();
}

public function test_pemilih_yang_sudah_memilih_tidak_bisa_dihapus(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->where('id_siswa', $d['id_siswa'][0])->update(['sudah_memilih' => 1]);
    $this->expectException(\Throwable::class);
    DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->where('id_siswa', $d['id_siswa'][0])->delete();
}

public function test_data_pemilihan_terkunci_saat_berlangsung(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->expectException(\Throwable::class);
    DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['waktu_selesai' => now()->addDays(1)]);
}

public function test_perubahan_status_tercatat_di_log(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);
    $log = DB::table('log_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->where('aksi', 'STATUS_BERUBAH')->first();
    $this->assertNotNull($log);
    $this->assertSame('berlangsung', $log->status_lama);
    $this->assertSame('selesai', $log->status_baru);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=PemilihanTriggerTest`
Expected: FAIL — trigger belum ada.

- [ ] **Step 3: Buat migration trigger**

Ikuti pola `000031_create_presensi_triggers.php`. Setiap trigger penjaga memakai `SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '...'`. `trg_before_pemilihan_update` hanya menolak bila `OLD.status <> 'draf'` **dan** ada kolom data yang berubah (`judul`, `waktu_mulai`, `waktu_selesai`, `id_organisasi`, `id_tahun_ajaran`, `deskripsi`); perubahan `status` tetap diizinkan. `trg_after_pemilihan_update` menulis log memakai `COALESCE(@id_user_app, NULL)`.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=PemilihanTriggerTest`
Expected: PASS 6 tests.

- [ ] **Step 5: Commit**

```bash
git add database/migrations tests/Feature/Voting/PemilihanTriggerTest.php
git commit -m "feat: trigger penjaga dan audit pemilihan"
```

---

### Task 6: `sp_rekam_suara` — jalur pemungutan suara

**Files:**
- Create: `database/migrations/2026_11_17_000006_create_rekam_suara_procedure.php`
- Test: `tests/Feature/Voting/RekamSuaraTest.php`

**Interfaces:**
- Consumes: `trg_before_suara_insert` (Task 5), `fn_total_suara` (Task 3).
- Produces: `sp_rekam_suara(IN p_id_pemilihan INT, IN p_id_siswa INT, IN p_id_kandidat INT, OUT p_status VARCHAR(40))`.

- [ ] **Step 1: Tulis test yang gagal**

```php
private function rekam(array $d, int $idSiswa, ?int $idKandidat = null): string
{
    DB::statement('CALL sp_rekam_suara(?,?,?,@status)', [$d['id_pemilihan'], $idSiswa, $idKandidat ?? $d['id_kandidat'][0]]);
    return DB::selectOne('SELECT @status AS s')->s;
}

public function test_suara_pertama_berhasil(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->assertSame('BERHASIL', $this->rekam($d, $d['id_siswa'][0]));
    $this->assertSame(1, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
    $this->assertSame(1, (int) DB::selectOne('SELECT fn_sudah_memilih(?,?) b', [$d['id_pemilihan'], $d['id_siswa'][0]])->b);
}

public function test_suara_kedua_ditolak(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->rekam($d, $d['id_siswa'][0]);
    $this->assertSame('SUDAH_MEMILIH', $this->rekam($d, $d['id_siswa'][0], $d['id_kandidat'][1]));
    $this->assertSame(1, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
}

public function test_bukan_pemilih_ditolak(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->assertSame('BUKAN_PEMILIH', $this->rekam($d, 999999));
}

public function test_kandidat_milik_pemilihan_lain_ditolak(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->assertSame('KANDIDAT_TIDAK_VALID', $this->rekam($d, $d['id_siswa'][0], 999999));
}

public function test_ditolak_saat_selesai(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);
    $this->assertSame('SUDAH_DITUTUP', $this->rekam($d, $d['id_siswa'][0]));
}

public function test_ditolak_di_luar_rentang_waktu(): void
{
    // Rentang waktu tidak bisa diubah saat berlangsung (dikunci trigger), jadi pemilihan
    // yang waktunya sudah lewat dibuat langsung lewat INSERT.
    $d = $this->seedPemilihanBerlangsung();
    $idBaru = DB::table('pemilihan')->insertGetId([
        'id_organisasi' => $d['id_organisasi'], 'id_tahun_ajaran' => $d['id_tahun_ajaran'],
        'judul' => 'Lewat Waktu', 'waktu_mulai' => now()->subHours(2), 'waktu_selesai' => now()->subMinute(),
        'status' => 'berlangsung', 'dibuat_oleh' => $d['id_admin'],
    ]);
    DB::table('daftar_pemilih')->insert(['id_pemilihan' => $idBaru, 'id_siswa' => $d['id_siswa'][0], 'sudah_memilih' => 0]);
    $idKandidat = DB::table('kandidat')->insertGetId([
        'id_pemilihan' => $idBaru, 'nomor_urut' => 1, 'id_siswa_ketua' => $d['id_siswa'][0],
    ]);

    DB::statement('CALL sp_rekam_suara(?,?,?,@status)', [$idBaru, $d['id_siswa'][0], $idKandidat]);
    $this->assertSame('SUDAH_DITUTUP', DB::selectOne('SELECT @status AS s')->s);
    $this->assertSame(0, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$idBaru])->t);
}

public function test_id_suara_acak_dan_tidak_berurutan(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->rekam($d, $d['id_siswa'][0]);
    $this->rekam($d, $d['id_siswa'][1]);
    $ids = DB::table('suara')->pluck('id_suara')->all();
    $this->assertCount(2, $ids);
    foreach ($ids as $id) {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
    }
    $this->assertNotSame(substr($ids[0], 0, 8), substr($ids[1], 0, 8));
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=RekamSuaraTest`
Expected: FAIL — procedure belum ada.

- [ ] **Step 3: Buat migration procedure**

Ikuti alur §8.2 di spec. Wajib: `UPDATE daftar_pemilih ... AND sudah_memilih = 0` lalu periksa `ROW_COUNT() = 0` untuk `SUDAH_MEMILIH`; UUID v4 dibuat dengan `RANDOM_BYTES` (bukan `UUID()`), memakai ekspresi yang sudah diverifikasi di spec. Procedure **tidak** membuka transaksi sendiri (controller yang mengelola), sama seperti `sp_scan_presensi`.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=RekamSuaraTest`
Expected: PASS 7 tests.

- [ ] **Step 5: Uji balapan dua koneksi**

Tambahkan test yang memanggil `sp_rekam_suara` dua kali berturut-turut tanpa `sleep` dan memastikan `fn_total_suara` tetap 1 (sudah tercakup `test_suara_kedua_ditolak`). Pastikan juga `DB::transaction()` di controller nanti tidak membuat `ROW_COUNT()` salah — periksa manual sekali dengan `php artisan tinker` sebelum lanjut.

- [ ] **Step 6: Commit**

```bash
git add database/migrations tests/Feature/Voting/RekamSuaraTest.php
git commit -m "feat: stored procedure perekaman suara"
```

---

### Task 7: Procedure pengelolaan pemilihan

**Files:**
- Create: `database/migrations/2026_11_17_000007_create_pemilihan_management_procedures.php`
- Test: `tests/Feature/Voting/KelolaProcedureTest.php`

**Interfaces:**
- Produces: `sp_simpan_pemilihan`, `sp_simpan_kandidat`, `sp_hapus_kandidat`, `sp_daftarkan_pemilih_kelas`, `sp_hapus_pemilih`, `sp_tunjuk_panitia`, `sp_buka_pemilihan`, `sp_tutup_pemilihan`, `sp_kembalikan_ke_draf` — semua dengan `IN p_id_user INT` dan `OUT p_status VARCHAR(30)` sesuai tabel §8.1 spec.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_buka_pemilihan_mengubah_status(): void
{
    $d = $this->seedPemilihanDraf();
    DB::statement('CALL sp_buka_pemilihan(?,?,@s)', [$d['id_pemilihan'], $d['id_admin']]);
    $this->assertSame('BERHASIL', DB::selectOne('SELECT @s AS s')->s);
    $this->assertSame('berlangsung', DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
}

public function test_buka_ditolak_tanpa_kandidat(): void
{
    $d = $this->seedPemilihanDraf();
    DB::table('kandidat')->where('id_pemilihan', $d['id_pemilihan'])->delete();
    DB::statement('CALL sp_buka_pemilihan(?,?,@s)', [$d['id_pemilihan'], $d['id_admin']]);
    $this->assertSame('BELUM_ADA_KANDIDAT', DB::selectOne('SELECT @s AS s')->s);
}

public function test_buka_ditolak_tanpa_pemilih(): void
{
    $d = $this->seedPemilihanDraf();
    DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->delete();
    DB::statement('CALL sp_buka_pemilihan(?,?,@s)', [$d['id_pemilihan'], $d['id_admin']]);
    $this->assertSame('BELUM_ADA_PEMILIH', DB::selectOne('SELECT @s AS s')->s);
}

public function test_bukan_admin_atau_panitia_ditolak(): void
{
    $d = $this->seedPemilihanDraf();
    DB::statement('CALL sp_buka_pemilihan(?,?,@s)', [$d['id_pemilihan'], $d['id_siswa_user']]);
    $this->assertSame('TIDAK_BERHAK', DB::selectOne('SELECT @s AS s')->s);
}

public function test_kembalikan_ke_draf_ditolak_bila_sudah_ada_suara(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);
    DB::statement('CALL sp_kembalikan_ke_draf(?,?,@s)', [$d['id_pemilihan'], $d['id_admin']]);
    $this->assertSame('SUDAH_ADA_SUARA', DB::selectOne('SELECT @s AS s')->s);
}

public function test_daftarkan_pemilih_kelas_idempoten(): void
{
    $d = $this->seedPemilihanDraf();
    DB::statement('CALL sp_daftarkan_pemilih_kelas(?,?,?,@s,@n)', [$d['id_pemilihan'], $d['id_admin'], $d['id_kelas']]);
    $pertama = (int) DB::selectOne('SELECT @n AS n')->n;
    DB::statement('CALL sp_daftarkan_pemilih_kelas(?,?,?,@s,@n)', [$d['id_pemilihan'], $d['id_admin'], $d['id_kelas']]);
    $kedua = (int) DB::selectOne('SELECT @n AS n')->n;
    $this->assertGreaterThan(0, $pertama);
    $this->assertSame(0, $kedua);
}

public function test_simpan_kandidat_ditolak_saat_berlangsung(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::statement(
        'CALL sp_simpan_kandidat(NULL, ?, ?, ?, ?, NULL, NULL, NULL, NULL, @s, @id)',
        [$d['id_admin'], $d['id_pemilihan'], 9, $d['id_siswa'][0]]
    );
    $this->assertSame('STATUS_TIDAK_VALID', DB::selectOne('SELECT @s AS s')->s);
    $this->assertSame(2, DB::table('kandidat')->where('id_pemilihan', $d['id_pemilihan'])->count());
}

public function test_panitia_boleh_mengelola_pemilihan_yang_ditugaskan(): void
{
    $d = $this->seedPemilihanDraf();
    DB::table('panitia_pemilihan')->insert(['id_pemilihan' => $d['id_pemilihan'], 'id_user' => $d['id_panitia_user']]);
    DB::statement('CALL sp_buka_pemilihan(?,?,@s)', [$d['id_pemilihan'], $d['id_panitia_user']]);
    $this->assertSame('BERHASIL', DB::selectOne('SELECT @s AS s')->s);
}
```

`seedPemilihanDraf()` harus juga mengembalikan `id_admin`, `id_panitia_user`, `id_siswa_user`, `id_kelas`.

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=KelolaProcedureTest`
Expected: FAIL.

- [ ] **Step 3: Buat migration procedure**

Satu helper logika di setiap procedure: periksa `p_id_user` adalah `admin` **atau** ada di `panitia_pemilihan` untuk pemilihan itu; jika tidak → `TIDAK_BERHAK`. `sp_tunjuk_panitia` hanya boleh admin. Semua procedure yang mengubah status menyetel `SET @id_user_app = p_id_user` sebelum `UPDATE pemilihan` agar trigger audit mencatat pelakunya.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=KelolaProcedureTest`
Expected: PASS 8 tests.

- [ ] **Step 5: Commit**

```bash
git add database/migrations tests/Feature/Voting/KelolaProcedureTest.php
git commit -m "feat: stored procedure pengelolaan pemilihan"
```

---

### Task 8: GRANT per role

**Files:**
- Create: `database/migrations/2026_11_17_000008_setup_pemilihan_grants.php`
- Test: `tests/Feature/Voting/GrantTest.php`

**Interfaces:**
- Consumes: seluruh objek dari Task 2–7.
- Produces: hak akses `admin_app`, `guru_app`, `siswa_app` sesuai tabel §8.1 spec.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_siswa_tidak_bisa_insert_suara_langsung(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->expectException(\Throwable::class);
    DB::connection('mysql_siswa')->insert('INSERT INTO suara (id_suara, id_pemilihan, id_kandidat) VALUES (?,?,?)', [Str::uuid()->toString(), $d['id_pemilihan'], $d['id_kandidat'][0]]);
}

public function test_siswa_tidak_bisa_update_daftar_pemilih(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->expectException(\Throwable::class);
    DB::connection('mysql_siswa')->update('UPDATE daftar_pemilih SET sudah_memilih = 1 WHERE id_pemilihan = ?', [$d['id_pemilihan']]);
}

public function test_siswa_bisa_execute_sp_rekam_suara(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::connection('mysql_siswa')->statement('CALL sp_rekam_suara(?,?,?,@s)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);
    $this->assertSame('BERHASIL', DB::connection('mysql_siswa')->selectOne('SELECT @s AS s')->s);
}

public function test_siswa_menerima_null_untuk_hitungan_sementara(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $baris = DB::connection('mysql_siswa')->select('SELECT jumlah_suara FROM v_hasil_pemilihan WHERE id_pemilihan = ?', [$d['id_pemilihan']]);
    $this->assertNull($baris[0]->jumlah_suara);
}

public function test_siswa_tidak_bisa_membaca_view_monitor(): void
{
    $this->expectException(\Throwable::class);
    DB::connection('mysql_siswa')->select('SELECT jumlah_suara FROM v_kandidat_pemilihan_hasil LIMIT 1');
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=GrantTest`
Expected: FAIL — siswa masih memakai hak lama / view belum di-grant.

- [ ] **Step 3: Buat migration GRANT**

Ikuti pola `000033`: `CREATE USER IF NOT EXISTS` untuk keempat akun, lalu `GRANT` per objek. **Jangan** memakai `GRANT ALL ON db.*` untuk role voting. Beri `admin_app` hak per objek (bukan `ALL ON db.*`) agar tabel §8.1 spec terpenuhi; hak `admin_app` atas tabel presensi tidak disentuh. Akhiri dengan `FLUSH PRIVILEGES`.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=GrantTest`
Expected: PASS 5 tests.

- [ ] **Step 5: Verifikasi manual hak akses**

Run: `php artisan tinker` lalu `DB::connection('mysql_siswa')->select('SHOW GRANTS FOR CURRENT_USER()')`.
Expected: ada `EXECUTE` pada `sp_rekam_suara`, **tidak ada** `INSERT` pada `suara`.

- [ ] **Step 6: Commit**

```bash
git add database/migrations tests/Feature/Voting/GrantTest.php
git commit -m "feat: grant least privilege untuk fitur pemilihan"
```

---

### Task 9: Model Eloquent

**Files:**
- Create: `app/Models/Pemilihan.php`, `Kandidat.php`, `DaftarPemilih.php`, `Suara.php`, `PanitiaPemilihan.php`, `LogPemilihan.php`
- Test: `tests/Feature/Voting/ModelTest.php`

**Interfaces:**
- Produces:
  - `Pemilihan` — PK `id_pemilihan`, `$timestamps = false`, relasi `kandidat()`, `pemilih()`, `panitia()`; scope `berlangsung()`, `selesai()`; method statis `bolehMemilih(int $idPemilihan, int $idSiswa): bool`, `sudahMemilih(...): bool`, `totalSuara(int): int`, `partisipasi(int): float` (memanggil function DB).
  - `Kandidat` — PK `id_kandidat`, `$timestamps = false`, relasi `ketua()`, `wakil()`.
  - `DaftarPemilih` — PK gabungan, `$incrementing = false`, `$timestamps = false`.
  - `Suara` — PK `id_suara` (string), `$incrementing = false`, `$timestamps = false`.
  - `PanitiaPemilihan` — PK gabungan, `$timestamps = false`.
  - `LogPemilihan` — PK `id_log`, `$timestamps = false`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_model_memanggil_stored_function(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->assertFalse(Pemilihan::sudahMemilih($d['id_pemilihan'], $d['id_siswa'][0]));
    $this->assertTrue(Pemilihan::bolehMemilih($d['id_pemilihan'], $d['id_siswa'][0]));
    $this->assertSame(0, Pemilihan::totalSuara($d['id_pemilihan']));
    $this->assertEqualsWithDelta(0.0, Pemilihan::partisipasi($d['id_pemilihan']), 0.001);
}

public function test_relasi_kandidat_ke_siswa(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $k = Kandidat::with('ketua')->find($d['id_kandidat'][0]);
    $this->assertNotNull($k->ketua);
    $this->assertNotEmpty($k->ketua->nama_siswa);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=ModelTest`
Expected: FAIL — class belum ada.

- [ ] **Step 3: Buat model**

Ikuti gaya `app/Models/Presensi.php` dan `Siswa.php` (properti `$table`, `$primaryKey`, `$timestamps`, `$guarded = []`). Method function DB memakai `DB::selectOne('SELECT fn_...(...) AS hasil', [...])` dan mengembalikan tipe yang sudah di-cast.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=ModelTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models tests/Feature/Voting/ModelTest.php
git commit -m "feat: model pemilihan"
```

---

### Task 10: Policy, route, dan middleware

**Files:**
- Create: `app/Policies/PemilihanPolicy.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Voting/OtorisasiTest.php`

**Interfaces:**
- Consumes: model Task 9.
- Produces: route bernama `siswa.pemilihan.index`, `siswa.pemilihan.pilih`, `siswa.pemilihan.simpan`, `siswa.pemilihan.hasil`, `kelola.pemilihan.index`, `kelola.pemilihan.form`, `kelola.pemilihan.simpan`, `kelola.pemilihan.kandidat`, `kelola.pemilihan.kandidat.simpan`, `kelola.pemilihan.kandidat.hapus`, `kelola.pemilihan.pemilih`, `kelola.pemilihan.pemilih.daftarkan`, `kelola.pemilihan.pemilih.hapus`, `kelola.pemilihan.panitia`, `kelola.pemilihan.panitia.simpan`, `kelola.pemilihan.monitor`, `kelola.pemilihan.buka`, `kelola.pemilihan.tutup`, `kelola.pemilihan.kembalikan`, `kelola.pemilihan.log`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_siswa_tidak_bisa_masuk_halaman_kelola(): void
{
    $d = $this->seedPemilihanDraf();
    $this->actingAs($this->userDari($d['id_siswa_user']))->get(route('kelola.pemilihan.index'))->assertForbidden();
}

public function test_guru_bukan_panitia_tidak_bisa_mengelola(): void
{
    $d = $this->seedPemilihanDraf();
    $this->actingAs($this->userDari($d['id_guru_lain']))->get(route('kelola.pemilihan.monitor', $d['id_pemilihan']))->assertForbidden();
}

public function test_panitia_bisa_membuka_monitor(): void
{
    $d = $this->seedPemilihanDraf();
    DB::table('panitia_pemilihan')->insert(['id_pemilihan' => $d['id_pemilihan'], 'id_user' => $d['id_panitia_user']]);
    $this->actingAs($this->userDari($d['id_panitia_user']))->get(route('kelola.pemilihan.monitor', $d['id_pemilihan']))->assertOk();
}

public function test_admin_bisa_membuka_daftar_kelola(): void
{
    $d = $this->seedPemilihanDraf();
    $this->actingAs($this->userDari($d['id_admin']))->get(route('kelola.pemilihan.index'))->assertOk();
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=OtorisasiTest`
Expected: FAIL — route belum ada.

- [ ] **Step 3: Buat policy dan route**

`PemilihanPolicy::kelola(User $user, ?Pemilihan $pemilihan = null): bool` → `true` bila `role === 'admin'`; bila `role === 'guru'` dan `$pemilihan` diberikan, cek `panitia_pemilihan`; selain itu `false`. Daftarkan di `AppServiceProvider::boot()` dengan `Gate::policy()`. Route: grup `auth,role:siswa` untuk siswa, grup `auth,role:admin,guru` untuk kelola; controller memanggil `$this->authorize('kelola', $pemilihan)`.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=OtorisasiTest`
Expected: PASS 4 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Policies routes/web.php app/Providers tests/Feature/Voting/OtorisasiTest.php
git commit -m "feat: policy dan route pemilihan"
```

---

### Task 11: Layout bergaya sekolah + halaman siswa (daftar & pilih)

**Files:**
- Create: `resources/views/layouts/pemilihan.blade.php`
- Create: `resources/views/siswa/pemilihan/index.blade.php`, `pilih.blade.php`
- Create: `app/Http/Controllers/SiswaPemilihanController.php`
- Test: `tests/Feature/Voting/SiswaPemilihanTest.php`

**Interfaces:**
- Consumes: `Pemilihan::bolehMemilih`, `Pemilihan::sudahMemilih`, `v_kandidat_pemilihan`.
- Produces: `SiswaPemilihanController::index()`, `pilih(int $id)`, `simpan(Request $request, int $id)`, `hasil(int $id)`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_siswa_melihat_daftar_pemilihan_yang_diikuti(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->actingAs($this->userDari($d['id_siswa_user'][0]))
        ->get(route('siswa.pemilihan.index'))
        ->assertOk()->assertSee('Pemilihan');
}

public function test_siswa_bisa_mengirim_suara(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->actingAs($this->userDari($d['id_siswa_user'][0]))
        ->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => $d['id_kandidat'][0]])
        ->assertRedirect();
    $this->assertSame(1, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
}

public function test_siswa_tidak_bisa_mengirim_suara_dua_kali_lewat_http(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $u = $this->userDari($d['id_siswa_user'][0]);
    $this->actingAs($u)->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => $d['id_kandidat'][0]]);
    $this->actingAs($u)->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => $d['id_kandidat'][1]])
        ->assertSessionHas('error');
    $this->assertSame(1, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
}

public function test_siswa_tidak_bisa_memilih_kandidat_dari_pemilihan_lain(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->actingAs($this->userDari($d['id_siswa_user'][0]))
        ->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => 999999])
        ->assertSessionHas('error');
    $this->assertSame(0, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=SiswaPemilihanTest`
Expected: FAIL.

- [ ] **Step 3: Buat layout dan view siswa**

`layouts/pemilihan.blade.php`: header teal `#09656F` dengan teks "SI-SMANSAKA", nav sederhana, `@yield('isi')`, alert `success`/`error`, tombol keluar. Palet dan bentuk kartu mengikuti `sman1kabanjahe.sch.id`. `index.blade.php`: kartu per pemilihan + status + tombol. `pilih.blade.php`: grid kartu kandidat + modal konfirmasi.

- [ ] **Step 4: Buat controller**

`simpan()`: validasi `id_kandidat` integer, ambil `id_siswa` dari `Auth::user()->siswa`, lalu `DB::beginTransaction()` → `DB::statement('CALL sp_rekam_suara(?,?,?,@status)', [...])` → `DB::selectOne('SELECT @status AS status')` → `commit()`; pada `QueryException` → `rollBack()` + `Log::error`. Petakan `p_status` ke pesan Indonesia.

- [ ] **Step 5: Jalankan test**

Run: `php artisan test --filter=SiswaPemilihanTest`
Expected: PASS 4 tests.

- [ ] **Step 6: Commit**

```bash
git add resources/views/layouts/pemilihan.blade.php resources/views/siswa/pemilihan app/Http/Controllers/SiswaPemilihanController.php tests/Feature/Voting/SiswaPemilihanTest.php
git commit -m "feat: halaman siswa untuk memilih"
```

---

### Task 12: Halaman hasil untuk siswa

**Files:**
- Create: `resources/views/siswa/pemilihan/hasil.blade.php`
- Modify: `app/Http/Controllers/SiswaPemilihanController.php`
- Test: `tests/Feature/Voting/HasilTest.php`

**Interfaces:**
- Consumes: `v_hasil_pemilihan`, `v_partisipasi_pemilihan`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_hasil_belum_tersedia_saat_berlangsung(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->actingAs($this->userDari($d['id_siswa_user'][0]))
        ->get(route('siswa.pemilihan.hasil', $d['id_pemilihan']))
        ->assertSessionHas('error');
}

public function test_hasil_tampil_setelah_selesai(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);
    $this->actingAs($this->userDari($d['id_siswa_user'][0]))
        ->get(route('siswa.pemilihan.hasil', $d['id_pemilihan']))
        ->assertOk()->assertSee('Perolehan Suara');
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=HasilTest`
Expected: FAIL.

- [ ] **Step 3: Implementasikan `hasil()` dan view**

Controller memeriksa status `selesai` lebih dulu (bila belum → redirect + `error`), lalu membaca `v_hasil_pemilihan` dan `v_partisipasi_pemilihan`. View menampilkan bar horizontal per kandidat, jumlah suara, persentase, penanda pemenang, dan ringkasan partisipasi.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=HasilTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/siswa/pemilihan/hasil.blade.php app/Http/Controllers/SiswaPemilihanController.php tests/Feature/Voting/HasilTest.php
git commit -m "feat: halaman hasil pemilihan untuk siswa"
```

---

### Task 13: Halaman kelola — daftar dan form pemilihan

**Files:**
- Create: `app/Http/Controllers/KelolaPemilihanController.php`
- Create: `app/Http/Requests/SimpanPemilihanRequest.php`
- Create: `resources/views/kelola/pemilihan/index.blade.php`, `form.blade.php`
- Test: `tests/Feature/Voting/KelolaPemilihanTest.php`

**Interfaces:**
- Consumes: `sp_simpan_pemilihan`, `v_pemilihan_pengelola`.
- Produces: `KelolaPemilihanController::index()`, `form(?int $id)`, `simpan(SimpanPemilihanRequest $request, ?int $id)`, `monitor(int $id)`, `buka(int $id)`, `tutup(int $id)`, `kembalikan(int $id)`, `log(int $id)`, `kandidat(int $id)`, `simpanKandidat(...)`, `hapusKandidat(...)`, `pemilih(int $id)`, `daftarkanPemilih(...)`, `hapusPemilih(...)`, `panitia(int $id)`, `simpanPanitia(...)`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_admin_membuat_pemilihan_baru_sebagai_draf(): void
{
    $d = $this->seedPemilihanDraf();
    $this->actingAs($this->userDari($d['id_admin']))
        ->post(route('kelola.pemilihan.simpan'), [
            'judul' => 'Pemilihan Ketua OSIS 2027',
            'deskripsi' => 'Uji',
            'id_organisasi' => $d['id_organisasi'],
            'id_tahun_ajaran' => $d['id_tahun_ajaran'],
            'waktu_mulai' => now()->addDay()->format('Y-m-d\TH:i'),
            'waktu_selesai' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect();
    $this->assertDatabaseHas('pemilihan', ['judul' => 'Pemilihan Ketua OSIS 2027', 'status' => 'draf']);
}

public function test_validasi_waktu_selesai_harus_setelah_mulai(): void
{
    $d = $this->seedPemilihanDraf();
    $this->actingAs($this->userDari($d['id_admin']))
        ->post(route('kelola.pemilihan.simpan'), [
            'judul' => 'X', 'id_organisasi' => $d['id_organisasi'], 'id_tahun_ajaran' => $d['id_tahun_ajaran'],
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'waktu_selesai' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('waktu_selesai');
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=KelolaPemilihanTest`
Expected: FAIL.

- [ ] **Step 3: Buat request, controller, dan view**

`SimpanPemilihanRequest`: `judul` required max 100, `deskripsi` nullable, `id_organisasi` exists, `id_tahun_ajaran` exists, `waktu_mulai` required date, `waktu_selesai` required date `after:waktu_mulai`. Controller memanggil `sp_simpan_pemilihan` dan memetakan `p_status`. View daftar memakai `v_pemilihan_pengelola`.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=KelolaPemilihanTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/KelolaPemilihanController.php app/Http/Requests resources/views/kelola tests/Feature/Voting/KelolaPemilihanTest.php
git commit -m "feat: halaman kelola pemilihan"
```

---

### Task 14: Kelola kandidat + unggah foto

**Files:**
- Create: `app/Http/Requests/SimpanKandidatRequest.php`
- Create: `resources/views/kelola/pemilihan/kandidat.blade.php`
- Modify: `app/Http/Controllers/KelolaPemilihanController.php`
- Test: `tests/Feature/Voting/KandidatTest.php`

**Interfaces:**
- Consumes: `sp_simpan_kandidat`, `sp_hapus_kandidat`.
- Produces: penyimpanan foto di `storage/app/public/kandidat/`, `kandidat.foto_url` berisi path relatif `kandidat/<nama-file>`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_admin_menambah_kandidat_dengan_foto(): void
{
    Storage::fake('public');
    $d = $this->seedPemilihanDraf();
    $this->actingAs($this->userDari($d['id_admin']))
        ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
            'nomor_urut' => 1, 'id_siswa_ketua' => $d['id_siswa'][0],
            'visi' => 'Visi', 'misi' => 'Misi',
            'foto' => UploadedFile::fake()->image('foto.jpg', 400, 400),
        ])->assertRedirect();
    $this->assertDatabaseCount('kandidat', 3);
    $foto = DB::table('kandidat')->where('nomor_urut', 1)->value('foto_url');
    $this->assertNotNull($foto);
    Storage::disk('public')->assertExists($foto);
}

public function test_file_bukan_gambar_ditolak(): void
{
    Storage::fake('public');
    $d = $this->seedPemilihanDraf();
    $this->actingAs($this->userDari($d['id_admin']))
        ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
            'nomor_urut' => 1, 'id_siswa_ketua' => $d['id_siswa'][0],
            'foto' => UploadedFile::fake()->create('dokumen.pdf', 100),
        ])->assertSessionHasErrors('foto');
    $this->assertDatabaseCount('kandidat', 2);
}

public function test_kandidat_tidak_bisa_ditambah_saat_berlangsung(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->actingAs($this->userDari($d['id_admin']))
        ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
            'nomor_urut' => 9, 'id_siswa_ketua' => $d['id_siswa'][0],
        ])->assertSessionHas('error');
    $this->assertDatabaseCount('kandidat', 2);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=KandidatTest`
Expected: FAIL.

- [ ] **Step 3: Implementasikan**

`SimpanKandidatRequest`: `nomor_urut` required integer min 1, `id_siswa_ketua` required exists, `id_siswa_wakil` nullable exists, `visi`/`misi` nullable string, `foto` nullable `image|mimes:jpg,jpeg,png,webp|max:2048`. Controller: bila ada `foto`, `$request->file('foto')->store('kandidat', 'public')`; bila gagal simpan procedure, hapus file yang sudah terunggah agar tidak menjadi sampah.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=KandidatTest`
Expected: PASS 3 tests.

- [ ] **Step 5: Buat storage link**

Run: `php artisan storage:link`
Expected: `public/storage` menunjuk ke `storage/app/public`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/SimpanKandidatRequest.php resources/views/kelola app/Http/Controllers/KelolaPemilihanController.php tests/Feature/Voting/KandidatTest.php
git commit -m "feat: kelola kandidat dan unggah foto"
```

---

### Task 15: Kelola daftar pemilih dan panitia

**Files:**
- Create: `resources/views/kelola/pemilihan/pemilih.blade.php`, `panitia.blade.php`
- Modify: `app/Http/Controllers/KelolaPemilihanController.php`
- Test: `tests/Feature/Voting/PemilihTest.php`

**Interfaces:**
- Consumes: `sp_daftarkan_pemilih_kelas`, `sp_hapus_pemilih`, `sp_tunjuk_panitia`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_admin_mendaftarkan_seluruh_kelas_sebagai_pemilih(): void
{
    $d = $this->seedPemilihanDraf();
    DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->delete();
    $this->actingAs($this->userDari($d['id_admin']))
        ->post(route('kelola.pemilihan.pemilih.daftarkan', $d['id_pemilihan']), ['id_kelas' => [$d['id_kelas']]])
        ->assertRedirect();
    $this->assertGreaterThan(0, DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->count());
}

public function test_pemilih_yang_sudah_memilih_tidak_bisa_dihapus_lewat_http(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->where('id_siswa', $d['id_siswa'][0])->update(['sudah_memilih' => 1]);
    $this->actingAs($this->userDari($d['id_admin']))
        ->delete(route('kelola.pemilihan.pemilih.hapus', [$d['id_pemilihan'], $d['id_siswa'][0]]))
        ->assertSessionHas('error');
    $this->assertDatabaseHas('daftar_pemilih', ['id_pemilihan' => $d['id_pemilihan'], 'id_siswa' => $d['id_siswa'][0]]);
}

public function test_panitia_tidak_bisa_menunjuk_panitia_lain(): void
{
    $d = $this->seedPemilihanDraf();
    DB::table('panitia_pemilihan')->insert(['id_pemilihan' => $d['id_pemilihan'], 'id_user' => $d['id_panitia_user']]);
    $this->actingAs($this->userDari($d['id_panitia_user']))
        ->post(route('kelola.pemilihan.panitia.simpan', $d['id_pemilihan']), ['id_user' => $d['id_guru_lain']])
        ->assertSessionHas('error');
    $this->assertDatabaseCount('panitia_pemilihan', 1);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=PemilihTest`
Expected: FAIL.

- [ ] **Step 3: Implementasikan**

View `pemilih.blade.php`: multi-select kelas + tabel pemilih berpaginasi (25 baris). View `panitia.blade.php`: hanya dirender untuk admin. Controller memanggil procedure dan memetakan `p_status` ke pesan.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=PemilihTest`
Expected: PASS 3 tests.

- [ ] **Step 5: Commit**

```bash
git add resources/views/kelola app/Http/Controllers/KelolaPemilihanController.php tests/Feature/Voting/PemilihTest.php
git commit -m "feat: kelola pemilih dan panitia"
```

---

### Task 16: Monitor, buka/tutup, dan log

**Files:**
- Create: `resources/views/kelola/pemilihan/monitor.blade.php`, `log.blade.php`
- Modify: `app/Http/Controllers/KelolaPemilihanController.php`
- Test: `tests/Feature/Voting/MonitorTest.php`

**Interfaces:**
- Consumes: `sp_buka_pemilihan`, `sp_tutup_pemilihan`, `sp_kembalikan_ke_draf`, `v_kandidat_pemilihan_hasil`, `v_partisipasi_pemilihan`, `log_pemilihan`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_monitor_menampilkan_hitungan_sementara_untuk_panitia(): void
{
    $d = $this->seedPemilihanBerlangsung();
    DB::statement('CALL sp_rekam_suara(?,?,?,@s)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);
    $this->actingAs($this->userDari($d['id_admin']))
        ->get(route('kelola.pemilihan.monitor', $d['id_pemilihan']))
        ->assertOk()
        ->assertSee('Monitor')
        ->assertSee('data-suarakandidat="' . $d['id_kandidat'][0] . ':1"', false);
}

public function test_admin_menutup_pemilihan_dan_hasil_terbuka(): void
{
    $d = $this->seedPemilihanBerlangsung();
    $this->actingAs($this->userDari($d['id_admin']))
        ->post(route('kelola.pemilihan.tutup', $d['id_pemilihan']))->assertRedirect();
    $this->assertSame('selesai', DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
}

public function test_peringatan_muncul_bila_waktu_sudah_lewat(): void
{
    // Waktu tidak bisa diubah saat berlangsung, jadi dibuat pemilihan baru yang sudah lewat.
    $d = $this->seedPemilihanBerlangsung();
    $idBaru = DB::table('pemilihan')->insertGetId([
        'id_organisasi' => $d['id_organisasi'], 'id_tahun_ajaran' => $d['id_tahun_ajaran'],
        'judul' => 'Lewat Waktu', 'waktu_mulai' => now()->subHours(2), 'waktu_selesai' => now()->subMinute(),
        'status' => 'berlangsung', 'dibuat_oleh' => $d['id_admin'],
    ]);
    $this->actingAs($this->userDari($d['id_admin']))
        ->get(route('kelola.pemilihan.monitor', $idBaru))
        ->assertSee('waktu sudah lewat');
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=MonitorTest`
Expected: FAIL.

- [ ] **Step 3: Implementasikan**

Monitor menampilkan `v_kandidat_pemilihan_hasil` + `v_partisipasi_pemilihan`, tombol Buka/Tutup/Kembalikan (tombol disembunyikan sesuai status), dan peringatan "waktu sudah lewat — silakan tutup" bila `waktu_selesai < now()` sementara status masih `berlangsung`. Halaman log membaca `log_pemilihan` terbaru lebih dulu.

Setiap kartu kandidat di monitor membawa atribut `data-suarakandidat="<id_kandidat>:<jumlah_suara>"` agar jumlah suara bisa diuji tanpa bergantung pada teks lain di halaman.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=MonitorTest`
Expected: PASS 3 tests.

- [ ] **Step 5: Commit**

```bash
git add resources/views/kelola app/Http/Controllers/KelolaPemilihanController.php tests/Feature/Voting/MonitorTest.php
git commit -m "feat: monitor, buka/tutup, dan log pemilihan"
```

---

### Task 17: Seeder data contoh

**Files:**
- Create: `database/seeders/PemilihanSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Voting/SeederTest.php`

**Interfaces:**
- Produces: data contoh idempoten — 1 tahun ajaran, 1 organisasi, 3 kelas, 9 siswa + akun `siswa1`..`siswa9` (password `password`), 1 pemilihan `berlangsung`, 3 kandidat, panitia dari guru yang ada.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_seeder_idempoten(): void
{
    $this->seed(PemilihanSeeder::class);
    $this->seed(PemilihanSeeder::class);
    $this->assertSame(1, DB::table('pemilihan')->count());
    $this->assertSame(3, DB::table('kandidat')->count());
    $this->assertSame(9, DB::table('siswa')->count());
}

public function test_akun_siswa_contoh_bisa_login(): void
{
    $this->seed(PemilihanSeeder::class);
    $this->assertTrue(Auth::attempt(['username' => 'siswa1', 'password' => 'password', 'is_active' => true]));
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=SeederTest`
Expected: FAIL.

- [ ] **Step 3: Buat seeder**

Gunakan `firstOrCreate`/cek keberadaan agar idempoten. Pemilihan contoh: judul "Pemilihan Ketua OSIS 2026/2027", `waktu_mulai = now()->subHour()`, `waktu_selesai = now()->addDays(7)`, status `berlangsung`. Tiga kandidat dengan nomor urut 1–3, visi/misi singkat. Daftarkan seluruh siswa dari ketiga kelas ke `daftar_pemilih`.

- [ ] **Step 4: Jalankan test**

Run: `php artisan test --filter=SeederTest`
Expected: PASS.

- [ ] **Step 5: Jalankan di database aplikasi**

Run: `php artisan db:seed --class=PemilihanSeeder`
Expected: data contoh masuk ke `msbd_25`. **Periksa dulu** `DB_DATABASE` di `.env` masih `msbd_25`.

- [ ] **Step 6: Commit**

```bash
git add database/seeders tests/Feature/Voting/SeederTest.php
git commit -m "feat: data contoh pemilihan"
```

---

### Task 18: Pemeriksaan akhir dan dokumentasi

**Files:**
- Create: `docs/PEMILIHAN_PANDUAN.md`
- Test: seluruh suite

- [ ] **Step 1: Jalankan seluruh test**

Run: `php artisan test`
Expected: PASS semua, tanpa test yang di-skip.

- [ ] **Step 2: Periksa `EXPLAIN`**

Run di tinker: `DB::select('EXPLAIN SELECT id_kandidat, COUNT(*) FROM suara WHERE id_pemilihan = 1 GROUP BY id_kandidat')`.
Expected: `key` memakai `idx_suara_pemilihan_kandidat`, bukan `NULL`.

- [ ] **Step 3: Periksa tampilan di ponsel**

Buka halaman siswa dan kelola pada lebar 375px. Expected: tidak ada scroll horizontal, kartu kandidat satu kolom, tombol tetap terjangkau.

- [ ] **Step 4: Tulis panduan singkat**

`docs/PEMILIHAN_PANDUAN.md`: cara menjalankan seeder, akun contoh, alur uji manual (admin buat → daftarkan kelas → tambah kandidat → buka → siswa memilih → tutup → hasil), dan daftar objek database yang dibuat beserta fungsinya.

- [ ] **Step 5: Commit**

```bash
git add docs/PEMILIHAN_PANDUAN.md
git commit -m "docs: panduan fitur pemilihan"
```

---

## Catatan Pelaksana

- **Jangan** menjalankan `php artisan migrate:fresh` pada database `msbd_25`. Test memakai `msbd_25_test`.
- Setiap kelas test fitur voting memakai `DatabaseTruncation` dengan daftar `$tablesToTruncate` di atas. Jangan memakai `RefreshDatabase` atau `DatabaseTransactions`: middleware mengganti koneksi default, sehingga transaksi test tidak terlihat oleh koneksi role.
- Urutan migration wajib: log & index → function → view → trigger → procedure rekam suara → procedure kelola → grant. Grant harus terakhir karena butuh objeknya sudah ada.
- Bila sebuah migration objek database perlu diubah setelah dijalankan, buat migration baru yang `DROP` lalu `CREATE` ulang; jangan mengedit migration lama.
- Trigger `trg_before_pemilihan_update` mengunci perubahan data saat status bukan `draf`. Karena itu test yang butuh pemilihan dengan waktu sudah lewat harus **membuat pemilihan baru lewat `INSERT`**, bukan mengubah `waktu_selesai` pemilihan yang sudah `berlangsung`.
- Perubahan `status` tetap diizinkan trigger (memang itu tujuan tombol Buka/Tutup).
- Semua view/procedure/trigger dibuat lewat `DB::unprepared()` di migration, mengikuti pola `000028`–`000033`.
