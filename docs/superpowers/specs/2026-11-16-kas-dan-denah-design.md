# Desain: Modul Kas Kelas & Denah Interaktif

Tanggal: 2026-11-16
Status: menunggu persetujuan
Stack: Laravel 13.40, PHP 8.3, Blade, Alpine.js 3, Tailwind CSS 3, MySQL

---

## 1. Ringkasan

Menambahkan dua modul ke aplikasi sekolah yang sudah ada:

1. **Kas kelas** — pencatatan pemasukan/pengeluaran, rekap saldo, matriks iuran bulanan per siswa, verifikasi pembayaran, dan ekspor laporan.
2. **Denah interaktif** — denah tempat duduk kelas (grid, meja guru, papan tulis, pintu) dan peta area sekolah (ruangan, lantai, fasilitas, status).

Hanya **admin** dan **siswa yang menjabat Bendahara** yang boleh mencatat/mengedit kas. Pengguna lain bersifat read-only dan hanya melihat kelasnya sendiri (siswa) atau kelas anaknya (orang tua).

## 2. Kondisi proyek saat ini

- Auth Breeze sudah berjalan dengan primary key kustom `users.id_user` dan kolom `password_hash`.
- Role yang ada: `admin`, `guru`, `siswa`, `orang_tua` (enum `App\Enums\Role`).
- Baru ada model `User`; belum ada model/controller untuk fitur sekolah.
- Skema migrasi sudah lengkap secara tabel, tetapi belum ada kode fitur.

Tabel yang sudah ada dan relevan:

| Tabel | Isi | Catatan |
|---|---|---|
| `transaksi_kas` | `id_transaksi`, `id_kelas`, `jenis`, `kategori`, `nominal`, `tanggal`, `keterangan`, `id_siswa`, `bukti_url`, `dicatat_oleh`, `is_batal`, `created_at` | Sudah mendukung pemasukan/pengeluaran dan bukti |
| `denah_kelas` | `id_denah`, `id_kelas`, `jumlah_baris`, `jumlah_kolom`, `posisi_meja_guru`, `berlaku_mulai`, `dibuat_oleh`, `is_aktif` | Ini denah **tempat duduk**, bukan peta sekolah |
| `posisi_duduk` | `id_posisi`, `id_denah`, `id_siswa`, `baris`, `kolom` | Unique per kursi dan per siswa |
| `pengurus_kelas` + `jabatan` | Jabatan siswa per kelas | Sumber penentuan "Bendahara" dan "Ketua Kelas" |
| `anggota_kelas` | Keanggotaan siswa per kelas | Untuk validasi iuran dan denah |
| `kelas` + `tahun_ajaran` | Kelas dan tahun ajaran aktif | `kelas.ruangan` berupa string, dipakai untuk mencocokkan ruangan |

### Konflik dengan spesifikasi

1. Spesifikasi menulis `trnasaksi_kas` dengan kolom Inggris (`transaction_date`, `type`, `amount`, dst). Proyek sudah punya `transaksi_kas` dengan kolom Indonesia. **Keputusan: pertahankan skema Indonesia**, petakan nama kolom, dan jangan buat tabel duplikat.
2. Spesifikasi memakai `denah_kelas` untuk peta area sekolah. Nama itu sudah dipakai untuk denah kursi. **Keputusan: tambah tabel `ruangan_sekolah`** untuk peta area, dan pertahankan `denah_kelas` untuk kursi.
3. Belum ada tabel status iuran. **Keputusan: tambah `iuran_kas`** karena verifikasi pembayaran butuh status, verifikator, waktu, dan tautan ke transaksi.

## 3. Pendekatan yang dipertimbangkan

| Pendekatan | Keterangan | Trade-off | Status |
|---|---|---|---|
| A. Ikuti spesifikasi literal | Buat `trnasaksi_kas` Inggris dan `denah_kelas` area | Duplikasi data, bentrok dengan migrasi yang ada, tidak konsisten dengan seluruh skema | Ditolak |
| B. Perluas skema yang ada | Pakai `transaksi_kas`, `denah_kelas`, `posisi_duduk`; tambah `iuran_kas` dan `ruangan_sekolah` | Konsisten, tidak ada migrasi destruktif, sedikit penamaan berbeda dari spesifikasi | **Dipilih** |
| C. Pisahkan REST API + SPA | Backend API murni, frontend Vue/React | Overkill untuk proyek Blade/Alpine, menambah build tool dan auth token | Ditolak |

**Alasan memilih B:** seluruh skema lain memakai Bahasa Indonesia dan primary key kustom. Menambah dua tabel baru jauh lebih aman daripada mengganti nama tabel yang sudah dimigrasikan.

## 4. Matriks hak akses

| Aksi | Admin | Bendahara (siswa) | Ketua Kelas (siswa) | Siswa lain | Orang Tua | Guru |
|---|---|---|---|---|---|---|
| Lihat rekap & transaksi kelas | Semua kelas | Kelasnya | Kelasnya | Kelasnya | Kelas anaknya | Kelas yang diampu/diwali |
| Tambah/edit/batalkan transaksi | ✅ | ✅ kelasnya | ❌ | ❌ | ❌ | ❌ |
| Generate & verifikasi iuran | ✅ | ✅ kelasnya | ❌ | ❌ | ❌ | ❌ |
| Lihat status iuran sendiri | ✅ | ✅ | ✅ | ✅ | ✅ anaknya | — |
| Export CSV / cetak laporan | ✅ | ✅ kelasnya | ❌ | ❌ | ❌ | ❌ |
| Lihat denah kursi & area | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Atur/swap/acak kursi | ✅ | ❌ | ✅ kelasnya | ❌ | ❌ | ❌ |
| CRUD & status ruangan sekolah | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

Catatan: "Bendahara" dan "Ketua Kelas" ditentukan dari `pengurus_kelas` → `jabatan.nama_jabatan` pada kelas dengan tahun ajaran aktif. Pencocokan memakai `str_contains(strtolower(nama_jabatan), 'bendahara')` dan `'ketua'`, sehingga variasi "Bendahara Umum" tetap dikenali.

## 5. Skema database

### 5.1 Pemetaan kolom spesifikasi → kolom proyek

| Spesifikasi | Proyek |
|---|---|
| `transaction_date` | `tanggal` |
| `type` | `jenis` (`pemasukan` / `pengeluaran`) |
| `category` | `kategori` |
| `amount` | `nominal` |
| `description` | `keterangan` |
| `receipt_image` | `bukti_url` (path file di storage publik) |
| `created_by` | `dicatat_oleh` |

### 5.2 Migrasi baru

Urutan file (dijalankan setelah seluruh migrasi lama):

```text
2026_11_17_000001_add_audit_columns_to_transaksi_kas_table.php
2026_11_17_000002_create_iuran_kas_table.php
2026_11_17_000003_create_ruangan_sekolah_table.php
2026_11_17_000004_add_posisi_pintu_to_denah_kelas_table.php
```

#### a. Audit pada `transaksi_kas`

```php
Schema::table('transaksi_kas', function (Blueprint $table) {
    $table->dateTime('updated_at')->nullable()->after('created_at');
    $table->string('alasan_batal', 255)->nullable()->after('is_batal');
    $table->unsignedInteger('dibatalkan_oleh')->nullable()->after('alasan_batal');

    $table->foreign('dibatalkan_oleh')->references('id_user')->on('users')->nullOnDelete();
    $table->index(['id_kelas', 'tanggal']);
    $table->index(['id_kelas', 'jenis']);
});
```

Alasan: transaksi tidak dihapus permanen. Tombol hapus mengubah `is_batal = true` agar audit trail tetap utuh. `updated_at` dicatat saat edit.

#### b. Tabel `iuran_kas`

```php
Schema::create('iuran_kas', function (Blueprint $table) {
    $table->increments('id_iuran');
    $table->unsignedInteger('id_kelas');
    $table->unsignedInteger('id_siswa');
    $table->char('periode', 7); // YYYY-MM
    $table->decimal('nominal', 12, 2);
    $table->enum('status', ['belum_bayar', 'menunggu', 'terverifikasi', 'ditolak'])
          ->default('belum_bayar');
    $table->string('bukti_url', 255)->nullable();
    $table->unsignedInteger('id_transaksi')->nullable()->unique();
    $table->unsignedInteger('diverifikasi_oleh')->nullable();
    $table->dateTime('diverifikasi_pada')->nullable();
    $table->string('catatan', 255)->nullable();
    $table->timestamps();

    $table->unique(['id_kelas', 'id_siswa', 'periode']);
    $table->index(['id_kelas', 'periode']);

    $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
    $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
    $table->foreign('id_transaksi')->references('id_transaksi')->on('transaksi_kas')->nullOnDelete();
    $table->foreign('diverifikasi_oleh')->references('id_user')->on('users')->nullOnDelete();
});
```

Satu baris = satu tagihan iuran seorang siswa pada satu bulan. `id_transaksi` unik memastikan satu iuran hanya menghasilkan satu pemasukan.

#### c. Tabel `ruangan_sekolah`

```php
Schema::create('ruangan_sekolah', function (Blueprint $table) {
    $table->increments('id_ruangan');
    $table->string('kode_ruangan', 20)->unique();
    $table->string('nama_ruangan', 100);
    $table->enum('kategori', [
        'kelas', 'laboratorium', 'perpustakaan', 'ruang_guru',
        'kantin', 'uks', 'toilet', 'lainnya',
    ]);
    $table->string('gedung', 50)->default('Gedung Utama');
    $table->unsignedTinyInteger('lantai')->default(1);
    $table->unsignedSmallInteger('kapasitas')->nullable();
    $table->json('fasilitas')->nullable();
    $table->text('deskripsi')->nullable();
    $table->unsignedSmallInteger('posisi_x')->default(0);
    $table->unsignedSmallInteger('posisi_y')->default(0);
    $table->unsignedTinyInteger('lebar_grid')->default(1);
    $table->unsignedTinyInteger('tinggi_grid')->default(1);
    $table->enum('status', ['tersedia', 'maintenance', 'tutup'])->default('tersedia');
    $table->time('jam_buka')->nullable();
    $table->time('jam_tutup')->nullable();
    $table->string('gambar_url', 255)->nullable();
    $table->timestamps();

    $table->index(['gedung', 'lantai']);
});
```

Pemetaan spesifikasi: `name`→`nama_ruangan`, `category`→`kategori`, `floor`→`lantai`, `description`→`deskripsi`, `facilities`→`fasilitas` (JSON), `capacity`→`kapasitas`, `coordinates`→`posisi_x`/`posisi_y` (satuan grid) + `lebar_grid`/`tinggi_grid`.

#### d. `posisi_pintu` pada `denah_kelas`

```php
Schema::table('denah_kelas', function (Blueprint $table) {
    $table->enum('posisi_pintu', ['kiri', 'kanan', 'belakang'])
          ->default('kanan')
          ->after('posisi_meja_guru');
});
```

### 5.3 Relasi

```text
tahun_ajaran 1 ── n kelas
kelas 1 ── n anggota_kelas n ── 1 siswa
kelas 1 ── n pengurus_kelas n ── 1 jabatan
kelas 1 ── n transaksi_kas n ── 0..1 siswa
kelas 1 ── n iuran_kas n ── 1 siswa
transaksi_kas 1 ── 0..1 iuran_kas
kelas 1 ── n denah_kelas 1 ── n posisi_duduk n ── 1 siswa
denah_kelas (aktif) ── menampilkan kursi
ruangan_sekolah ── dicocokkan dengan kelas.ruangan (kode_ruangan)
```

## 6. Model & enum

### 6.1 Enum baru

```php
// app/Enums/JenisKas.php
enum JenisKas: string
{
    case Pemasukan = 'pemasukan';
    case Pengeluaran = 'pengeluaran';

    public function label(): string
    {
        return $this->value === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
    }

    public function warna(): string
    {
        return $this->value === 'pemasukan' ? 'emerald' : 'rose';
    }
}

// app/Enums/StatusIuran.php
enum StatusIuran: string
{
    case BelumBayar = 'belum_bayar';
    case Menunggu = 'menunggu';
    case Terverifikasi = 'terverifikasi';
    case Ditolak = 'ditolak';

    public function label(): string { /* ... */ }
    public function warna(): string { /* gray | amber | emerald | rose */ }
}

// app/Enums/KategoriKas.php — daftar nilai yang diizinkan
enum KategoriKas: string
{
    case IuranRutin = 'Iuran Rutin';
    case Denda = 'Denda';
    case Donasi = 'Donasi';
    case Konsumsi = 'Konsumsi';
    case Perlengkapan = 'Perlengkapan';
    case Kegiatan = 'Kegiatan';
    case Lainnya = 'Lainnya';

    public static function values(): array { return array_column(self::cases(), 'value'); }
}

// app/Enums/KategoriRuangan.php
// app/Enums/StatusRuangan.php
```

### 6.2 Model inti (contoh)

```php
#[Table('transaksi_kas', key: 'id_transaksi')]
#[Fillable([
    'id_kelas', 'jenis', 'kategori', 'nominal', 'tanggal', 'keterangan',
    'id_siswa', 'bukti_url', 'dicatat_oleh', 'is_batal', 'alasan_batal', 'dibatalkan_oleh',
])]
class TransaksiKas extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
            'is_batal' => 'boolean',
            'jenis' => JenisKas::class,
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh', 'id_user');
    }

    public function iuran(): HasOne
    {
        return $this->hasOne(IuranKas::class, 'id_transaksi', 'id_transaksi');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_batal', false);
    }
}
```

```php
#[Table('iuran_kas', key: 'id_iuran')]
#[Fillable([
    'id_kelas', 'id_siswa', 'periode', 'nominal', 'status', 'bukti_url',
    'id_transaksi', 'diverifikasi_oleh', 'diverifikasi_pada', 'catatan',
])]
class IuranKas extends Model
{
    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'status' => StatusIuran::class,
            'diverifikasi_pada' => 'datetime',
        ];
    }

    public function kelas(): BelongsTo { /* ... */ }
    public function siswa(): BelongsTo { /* ... */ }
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'id_transaksi', 'id_transaksi');
    }
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh', 'id_user');
    }
}
```

Model lain: `RuanganSekolah`, `DenahKelas` (`timestamps: false`), `PosisiDuduk` (`timestamps: false`), `Kelas`, `Siswa`, `AnggotaKelas`, `PengurusKelas`, `Jabatan`, `TahunAjaran`, `Guru`, `OrangTua`, semua dengan `#[Table(... key: ...)]` sesuai primary key migrasi.

### 6.3 Relasi pendukung di `User`

```php
public function siswa(): HasOne
{
    return $this->hasOne(Siswa::class, 'id_user', 'id_user');
}

public function guru(): HasOne
{
    return $this->hasOne(Guru::class, 'id_user', 'id_user');
}

public function orangTua(): HasOne
{
    return $this->hasOne(OrangTua::class, 'id_user', 'id_user');
}
```

## 7. Otorisasi

Satu service `App\Support\Akses` dipakai oleh Gate, controller, dan policy agar aturannya tidak tersebar.

```php
final class Akses
{
    public function kelasAktifSiswa(User $user): ?Kelas
    {
        $siswa = $user->siswa;
        if (! $siswa) return null;

        return Kelas::query()
            ->whereHas('tahunAjaran', fn ($q) => $q->where('is_aktif', true))
            ->whereHas('anggotaKelas', fn ($q) => $q->where('id_siswa', $siswa->id_siswa))
            ->first();
    }

    public function namaJabatan(User $user, Kelas $kelas): Collection
    {
        $siswa = $user->siswa;
        if (! $siswa) return collect();

        return Jabatan::query()
            ->whereHas('pengurusKelas', fn ($q) => $q
                ->where('id_kelas', $kelas->id_kelas)
                ->where('id_siswa', $siswa->id_siswa))
            ->pluck('nama_jabatan');
    }

    public function isBendahara(User $user, ?Kelas $kelas = null): bool
    {
        $kelas ??= $this->kelasAktifSiswa($user);
        if (! $kelas) return false;

        return $this->namaJabatan($user, $kelas)
            ->contains(fn ($j) => str_contains(strtolower($j), 'bendahara'));
    }

    public function isKetuaKelas(User $user, ?Kelas $kelas = null): bool
    {
        $kelas ??= $this->kelasAktifSiswa($user);
        if (! $kelas) return false;

        return $this->namaJabatan($user, $kelas)
            ->contains(fn ($j) => str_contains(strtolower($j), 'ketua'));
    }

    public function bisaLihatKelas(User $user, Kelas $kelas): bool
    {
        if ($user->role === Role::Admin) return true;

        if ($user->role === Role::Siswa) {
            return $this->kelasAktifSiswa($user)?->id_kelas === $kelas->id_kelas;
        }

        if ($user->role === Role::Guru) {
            $guru = $user->guru;
            if (! $guru) return false;

            return $kelas->id_wali_kelas === $guru->id_guru
                || $kelas->jadwalPelajaran()->where('id_guru', $guru->id_guru)->exists();
        }

        if ($user->role === Role::OrangTua) {
            $ortu = $user->orangTua;
            if (! $ortu) return false;

            return $ortu->siswa()
                ->whereHas('anggotaKelas', fn ($q) => $q->where('id_kelas', $kelas->id_kelas))
                ->exists();
        }

        return false;
    }

    public function bisaKelolaKas(User $user, Kelas $kelas): bool
    {
        return $user->role === Role::Admin || $this->isBendahara($user, $kelas);
    }

    public function bisaKelolaKursi(User $user, Kelas $kelas): bool
    {
        return $user->role === Role::Admin || $this->isKetuaKelas($user, $kelas);
    }
}
```

Gate di `AppServiceProvider::boot()`:

```php
Gate::define('kas.lihat', fn (User $u, Kelas $k) => app(Akses::class)->bisaLihatKelas($u, $k));
Gate::define('kas.kelola', fn (User $u, Kelas $k) => app(Akses::class)->bisaKelolaKas($u, $k));
Gate::define('denah.kelola-kursi', fn (User $u, Kelas $k) => app(Akses::class)->bisaKelolaKursi($u, $k));
Gate::define('denah.kelola-ruangan', fn (User $u) => $u->role === Role::Admin);
```

Semua controller memanggil `Gate::authorize(...)` sebelum membaca/menulis. Untuk request non-admin, `id_kelas` diabaikan dan dipaksa ke kelas milik user.

## 8. Route

Semua route di dalam middleware `auth`.

### Kas

```php
Route::prefix('kas')->name('kas.')->group(function () {
    Route::get('/', [KasController::class, 'index'])->name('index');
    Route::get('/data', [KasController::class, 'data'])->name('data');
    Route::post('/transaksi', [TransaksiKasController::class, 'store'])->name('transaksi.store');
    Route::put('/transaksi/{transaksi}', [TransaksiKasController::class, 'update'])->name('transaksi.update');
    Route::delete('/transaksi/{transaksi}', [TransaksiKasController::class, 'batal'])->name('transaksi.batal');

    Route::post('/iuran/generate', [IuranKasController::class, 'generate'])->name('iuran.generate');
    Route::patch('/iuran/{iuran}/verifikasi', [IuranKasController::class, 'verifikasi'])->name('iuran.verifikasi');
    Route::patch('/iuran/{iuran}/batal', [IuranKasController::class, 'batal'])->name('iuran.batal');

    Route::get('/export', [ExportKasController::class, 'csv'])->name('export');
    Route::get('/cetak', [ExportKasController::class, 'cetak'])->name('cetak');
});
```

### Denah

```php
Route::prefix('denah')->name('denah.')->group(function () {
    Route::get('/', [DenahController::class, 'index'])->name('index');
    Route::get('/kelas/{kelas}/data', [DenahKelasController::class, 'data'])->name('kelas.data');
    Route::put('/kelas/{kelas}', [DenahKelasController::class, 'simpan'])->name('kelas.simpan');
    Route::patch('/posisi/{posisi}', [DenahKelasController::class, 'pindah'])->name('posisi.pindah');
    Route::post('/kelas/{kelas}/swap', [DenahKelasController::class, 'swap'])->name('kelas.swap');
    Route::post('/kelas/{kelas}/acak', [DenahKelasController::class, 'acak'])->name('kelas.acak');

    Route::get('/sekolah/data', [RuanganSekolahController::class, 'data'])->name('sekolah.data');
    Route::post('/ruangan', [RuanganSekolahController::class, 'store'])->name('ruangan.store');
    Route::put('/ruangan/{ruangan}', [RuanganSekolahController::class, 'update'])->name('ruangan.update');
    Route::delete('/ruangan/{ruangan}', [RuanganSekolahController::class, 'destroy'])->name('ruangan.destroy');
    Route::patch('/ruangan/{ruangan}/status', [RuanganSekolahController::class, 'status'])->name('ruangan.status');
});
```

Navigasi: tambah link **Kas** dan **Denah** di `layouts/navigation.blade.php` (desktop + mobile).

## 9. Logika controller & service

### 9.1 `KasController`

Tanggung jawab: menentukan kelas yang boleh dilihat, ringkasan, filter, dan data matriks.

```php
public function index(Request $request): View
{
    $kelas = $this->kelasTerpilih($request);
    Gate::authorize('kas.lihat', $kelas);

    return view('kas.index', [
        'kelas' => $kelas,
        'daftarKelas' => $this->daftarKelasUntuk($request->user()),
        'bisaKelola' => Gate::allows('kas.kelola', $kelas),
        'initial' => $this->payload($request, $kelas),
    ]);
}
```

`payload()` mengembalikan:

```php
[
    'ringkasan' => [
        'saldo' => 0.0,
        'pemasukan_bulan_ini' => 0.0,
        'pengeluaran_bulan_ini' => 0.0,
        'total_pemasukan' => 0.0,
        'total_pengeluaran' => 0.0,
    ],
    'transaksi' => [...], // paginated
    'matriks' => [
        'tahun' => 2026,
        'bulan' => ['01' => 'Jan', ...],
        'siswa' => [
            ['id_siswa' => 1, 'nama' => '...', 'iuran' => ['01' => ['status' => 'terverifikasi', 'id_iuran' => 3]]],
        ],
    ],
]
```

**Perhitungan ringkasan** (selalu mengabaikan `is_batal = true`):

```php
$base = TransaksiKas::query()->where('id_kelas', $kelas->id_kelas)->aktif();

$totalPemasukan = (clone $base)->where('jenis', 'pemasukan')->sum('nominal');
$totalPengeluaran = (clone $base)->where('jenis', 'pengeluaran')->sum('nominal');

$awalBulan = now()->startOfMonth();
$akhirBulan = now()->endOfMonth();

$pemasukanBulanIni = (clone $base)
    ->where('jenis', 'pemasukan')
    ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
    ->sum('nominal');

$pengeluaranBulanIni = (clone $base)
    ->where('jenis', 'pengeluaran')
    ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
    ->sum('nominal');

$saldo = $totalPemasukan - $totalPengeluaran;
```

**Filter daftar transaksi**:

```php
$query = (clone $base)
    ->with(['pencatat:id_user,username', 'siswa:id_siswa,nama_siswa'])
    ->when($request->dari, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
    ->when($request->sampai, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))
    ->when($request->jenis, fn ($q, $v) => $q->where('jenis', $v))
    ->when($request->kategori, fn ($q, $v) => $q->where('kategori', $v))
    ->when($request->q, fn ($q, $v) => $q->where(function ($sub) use ($v) {
        $sub->where('keterangan', 'like', "%{$v}%")
            ->orWhere('kategori', 'like', "%{$v}%");
    }))
    ->orderByDesc('tanggal')
    ->orderByDesc('id_transaksi');
```

**Matriks iuran**: ambil semua `iuran_kas` kelas + tahun berjalan, group per siswa/periode. Siswa yang belum punya baris ditampilkan "belum_bayar" (null). Admin/bendahara bisa generate tagihan untuk bulan tertentu.

**Pemilihan kelas**:

```php
private function kelasTerpilih(Request $request): Kelas
{
    $user = $request->user();
    $akses = app(Akses::class);

    if ($user->role !== Role::Admin) {
        return $akses->kelasAktifSiswa($user)
            ?? $this->kelasUntukGuruAtauOrtu($user)
            ?? abort(403, 'Anda tidak terhubung ke kelas mana pun.');
    }

    $id = $request->integer('id_kelas');
    return $id
        ? Kelas::findOrFail($id)
        : Kelas::query()->whereHas('tahunAjaran', fn ($q) => $q->where('is_aktif', true))->firstOrFail();
}
```

### 9.2 `TransaksiKasController`

```php
public function store(StoreTransaksiKasRequest $request): JsonResponse
{
    $kelas = Kelas::findOrFail($request->integer('id_kelas'));
    Gate::authorize('kas.kelola', $kelas);

    $data = $request->validated();
    $data['dicatat_oleh'] = $request->user()->id_user;

    if ($request->hasFile('bukti')) {
        $data['bukti_url'] = $request->file('bukti')->store('kas/bukti', 'public');
    }

    $transaksi = DB::transaction(fn () => TransaksiKas::create($data));

    return response()->json([
        'message' => 'Transaksi berhasil dicatat.',
        'transaksi' => $transaksi->load('pencatat:id_user,username'),
    ], 201);
}

public function update(UpdateTransaksiKasRequest $request, TransaksiKas $transaksi): JsonResponse
{
    Gate::authorize('kas.kelola', $transaksi->kelas);
    abort_if($transaksi->is_batal, 422, 'Transaksi yang dibatalkan tidak dapat diedit.');

    $data = $request->validated();
    if ($request->hasFile('bukti')) {
        $data['bukti_url'] = $request->file('bukti')->store('kas/bukti', 'public');
    }

    $transaksi->update($data);

    return response()->json(['message' => 'Transaksi diperbarui.', 'transaksi' => $transaksi->fresh()]);
}

public function batal(Request $request, TransaksiKas $transaksi): JsonResponse
{
    Gate::authorize('kas.kelola', $transaksi->kelas);
    abort_if($transaksi->is_batal, 422, 'Transaksi sudah dibatalkan.');

    $request->validate(['alasan_batal' => ['required', 'string', 'max:255']]);

    DB::transaction(function () use ($request, $transaksi) {
        $transaksi->update([
            'is_batal' => true,
            'alasan_batal' => $request->string('alasan_batal'),
            'dibatalkan_oleh' => $request->user()->id_user,
        ]);

        // Jika transaksi adalah hasil verifikasi iuran, kembalikan status iuran.
        $transaksi->iuran?->update([
            'status' => StatusIuran::BelumBayar,
            'id_transaksi' => null,
            'diverifikasi_oleh' => null,
            'diverifikasi_pada' => null,
        ]);
    });

    return response()->json(['message' => 'Transaksi dibatalkan.']);
}
```

### 9.3 `IuranKasController`

**Generate tagihan bulanan** (idempoten):

```php
public function generate(GenerateIuranRequest $request, Kelas $kelas): JsonResponse
{
    Gate::authorize('kas.kelola', $kelas);

    $periode = $request->string('periode'); // YYYY-MM
    $nominal = $request->float('nominal');
    $dibuat = 0;

    DB::transaction(function () use ($kelas, $periode, $nominal, &$dibuat) {
        $anggota = $kelas->anggotaKelas()->pluck('id_siswa');

        foreach ($anggota as $idSiswa) {
            $iuran = IuranKas::firstOrCreate(
                ['id_kelas' => $kelas->id_kelas, 'id_siswa' => $idSiswa, 'periode' => $periode],
                ['nominal' => $nominal, 'status' => StatusIuran::BelumBayar],
            );
            $dibuat += $iuran->wasRecentlyCreated ? 1 : 0;
        }
    });

    return response()->json(['message' => "{$dibuat} tagihan dibuat.", 'dibuat' => $dibuat]);
}
```

**Verifikasi** — membuat transaksi pemasukan tepat satu kali:

```php
public function verifikasi(VerifikasiIuranRequest $request, IuranKas $iuran): JsonResponse
{
    Gate::authorize('kas.kelola', $iuran->kelas);

    DB::transaction(function () use ($request, $iuran) {
        $iuran->refresh();

        if ($iuran->status === StatusIuran::Terverifikasi) {
            abort(422, 'Iuran ini sudah terverifikasi.');
        }

        if ($request->string('status') === StatusIuran::Ditolak->value) {
            $iuran->update([
                'status' => StatusIuran::Ditolak,
                'catatan' => $request->string('catatan'),
                'diverifikasi_oleh' => $request->user()->id_user,
                'diverifikasi_pada' => now(),
            ]);
            return;
        }

        $transaksi = TransaksiKas::create([
            'id_kelas' => $iuran->id_kelas,
            'jenis' => JenisKas::Pemasukan,
            'kategori' => KategoriKas::IuranRutin->value,
            'nominal' => $iuran->nominal,
            'tanggal' => now()->toDateString(),
            'keterangan' => "Iuran kas periode {$iuran->periode}",
            'id_siswa' => $iuran->id_siswa,
            'dicatat_oleh' => $request->user()->id_user,
        ]);

        $iuran->update([
            'status' => StatusIuran::Terverifikasi,
            'id_transaksi' => $transaksi->id_transaksi,
            'diverifikasi_oleh' => $request->user()->id_user,
            'diverifikasi_pada' => now(),
            'catatan' => $request->string('catatan') ?: null,
        ]);
    });

    return response()->json(['message' => 'Iuran diverifikasi.', 'iuran' => $iuran->fresh()]);
}
```

**Batal verifikasi**: transaksi terkait di-`is_batal`, iuran kembali `belum_bayar`, `id_transaksi = null`.

### 9.4 `ExportKasController`

- `csv()`: stream CSV dengan BOM UTF-8, kolom `Tanggal, Jenis, Kategori, Nominal, Keterangan, Siswa, Dicatat Oleh, Status`; diakhiri baris ringkasan.
- `cetak()`: view `kas.cetak` berisi kop sekolah, ringkasan, tabel transaksi, dan tanda tangan bendahara. Tombol "Cetak / Simpan PDF" memanggil `window.print()`.
- Alasan tidak memakai DomPDF: belum ada dependensi PDF, dan `window.print()` sudah memenuhi kebutuhan "PDF atau Excel/CSV" tanpa menambah paket.

### 9.5 `DenahKelasController`

**Ambil data**:

```php
public function data(Request $request, Kelas $kelas): JsonResponse
{
    Gate::authorize('kas.lihat', $kelas);

    $denah = DenahKelas::query()
        ->where('id_kelas', $kelas->id_kelas)
        ->where('is_aktif', true)
        ->with(['posisiDuduk.siswa:id_siswa,nama_siswa,nis', 'posisiDuduk.siswa.pengurusKelas' => fn ($q) => $q->where('id_kelas', $kelas->id_kelas)])
        ->first();

    return response()->json([
        'denah' => $denah,
        'bisa_kelola' => Gate::allows('denah.kelola-kursi', $kelas),
        'siswa' => $kelas->siswa()->select('siswa.id_siswa', 'nama_siswa', 'nis')->get(),
    ]);
}
```

**Simpan layout** (baris, kolom, posisi guru, pintu):

```php
public function simpan(SimpanDenahRequest $request, Kelas $kelas): JsonResponse
{
    Gate::authorize('denah.kelola-kursi', $kelas);

    $denah = DB::transaction(function () use ($request, $kelas) {
        $denah = DenahKelas::query()
            ->where('id_kelas', $kelas->id_kelas)
            ->where('is_aktif', true)
            ->first();

        if ($denah) {
            $denah->update($request->validated());
            return $denah;
        }

        return DenahKelas::create([
            ...$request->validated(),
            'id_kelas' => $kelas->id_kelas,
            'berlaku_mulai' => now()->toDateString(),
            'dibuat_oleh' => $request->user()->id_user,
            'is_aktif' => true,
        ]);
    });

    return response()->json(['message' => 'Denah disimpan.', 'denah' => $denah->fresh()]);
}
```

**Pindah kursi**:

```php
public function pindah(PindahPosisiRequest $request, PosisiDuduk $posisi): JsonResponse
{
    Gate::authorize('denah.kelola-kursi', $posisi->denah->kelas);

    $request->validate([
        'baris' => ['required', 'integer', 'min:1', 'max:' . $posisi->denah->jumlah_baris],
        'kolom' => ['required', 'integer', 'min:1', 'max:' . $posisi->denah->jumlah_kolom],
    ]);

    $bentrok = PosisiDuduk::query()
        ->where('id_denah', $posisi->id_denah)
        ->where('baris', $request->integer('baris'))
        ->where('kolom', $request->integer('kolom'))
        ->where('id_posisi', '!=', $posisi->id_posisi)
        ->exists();

    abort_if($bentrok, 422, 'Kursi tujuan sudah terisi. Gunakan fitur tukar posisi.');

    $posisi->update($request->only(['baris', 'kolom']));

    return response()->json(['message' => 'Posisi dipindahkan.', 'posisi' => $posisi->fresh()]);
}
```

**Tukar posisi** — memakai kursi sementara (`baris = 255, kolom = 255`) agar unique constraint tidak dilanggar:

```php
public function swap(SwapPosisiRequest $request, Kelas $kelas): JsonResponse
{
    Gate::authorize('denah.kelola-kursi', $kelas);

    $a = PosisiDuduk::findOrFail($request->integer('posisi_a'));
    $b = PosisiDuduk::findOrFail($request->integer('posisi_b'));

    abort_unless($a->id_denah === $b->id_denah, 422, 'Posisi harus berada di denah yang sama.');

    DB::transaction(function () use ($a, $b) {
        $aAsli = [$a->baris, $a->kolom];

        $a->update(['baris' => 255, 'kolom' => 255]);
        $b->update(['baris' => $aAsli[0], 'kolom' => $aAsli[1]]);
        $a->update(['baris' => $b->baris, 'kolom' => $b->kolom]); // nilai b sebelum diubah
    });

    // Catatan implementasi: simpan nilai b sebelum update agar tidak tertukar.
    return response()->json(['message' => 'Posisi ditukar.']);
}
```

**Acak posisi**:

```php
public function acak(Request $request, Kelas $kelas): JsonResponse
{
    Gate::authorize('denah.kelola-kursi', $kelas);

    $denah = DenahKelas::query()
        ->where('id_kelas', $kelas->id_kelas)
        ->where('is_aktif', true)
        ->firstOrFail();

    $siswa = $kelas->anggotaKelas()->pluck('id_siswa')->shuffle()->values();

    abort_if(
        $siswa->count() > $denah->jumlah_baris * $denah->jumlah_kolom,
        422,
        'Jumlah siswa melebihi kapasitas kursi.'
    );

    DB::transaction(function () use ($denah, $siswa) {
        PosisiDuduk::where('id_denah', $denah->id_denah)->delete();

        $index = 0;
        for ($baris = 1; $baris <= $denah->jumlah_baris; $baris++) {
            for ($kolom = 1; $kolom <= $denah->jumlah_kolom; $kolom++) {
                if (! isset($siswa[$index])) {
                    return;
                }

                PosisiDuduk::create([
                    'id_denah' => $denah->id_denah,
                    'id_siswa' => $siswa[$index],
                    'baris' => $baris,
                    'kolom' => $kolom,
                ]);

                $index++;
            }
        }
    });

    return response()->json(['message' => 'Posisi diacak.']);
}
```

Perbaikan swap (implementasi final):

```php
$aBaris = $a->baris; $aKolom = $a->kolom;
$bBaris = $b->baris; $bKolom = $b->kolom;

$a->update(['baris' => 255, 'kolom' => 255]);
$b->update(['baris' => $aBaris, 'kolom' => $aKolom]);
$a->update(['baris' => $bBaris, 'kolom' => $bKolom]);
```

### 9.6 `RuanganSekolahController`

- `data()`: filter `gedung`, `lantai`, `kategori`, `q`; kembalikan `ruangan` + jadwal hari ini.
- `store/update/destroy`: `Gate::authorize('denah.kelola-ruangan')`; validasi field.
- `status()`: hanya ubah `status` (`tersedia`/`maintenance`/`tutup`).
- Jadwal saat ini dihitung dari `jadwal_pelajaran` yang `hari = hari ini`, `jam_mulai <= now <= jam_selesai`, dan `kelas.ruangan = ruangan.kode_ruangan`.

## 10. Validasi request

| Request | Aturan |
|---|---|
| `StoreTransaksiKasRequest` | `id_kelas` exists; `tanggal` required, date, `before_or_equal:today`; `jenis` in `pemasukan,pengeluaran`; `kategori` in `KategoriKas::values()`; `nominal` required numeric min 1 max 999999999999.99; `keterangan` required max 255; `id_siswa` nullable, harus anggota kelas; `bukti` nullable image mimes jpg,jpeg,png,webp max 2048 |
| `UpdateTransaksiKasRequest` | Sama, semua `sometimes` |
| `GenerateIuranRequest` | `periode` required `date_format:Y-m`; `nominal` required numeric min 1 |
| `VerifikasiIuranRequest` | `status` in `terverifikasi,ditolak`; `catatan` required_if `ditolak`, max 255 |
| `SimpanDenahRequest` | `jumlah_baris` 1–12; `jumlah_kolom` 1–10; `posisi_meja_guru` in `depan_kiri,depan_tengah,depan_kanan`; `posisi_pintu` in `kiri,kanan,belakang` |
| `PindahPosisiRequest` | `baris`/`kolom` dalam batas denah |
| `SwapPosisiRequest` | `posisi_a`/`posisi_b` exists dan satu denah |
| `StoreRuanganRequest` | `kode_ruangan` unique max 20; `nama_ruangan` required max 100; `kategori` enum; `lantai` 1–20; `kapasitas` nullable integer min 1; `fasilitas` array of string; `posisi_x`/`posisi_y` 0–100; `lebar_grid`/`tinggi_grid` 1–10; `status` enum; `jam_buka`/`jam_tutup` nullable `date_format:H:i` |

Validasi tambahan di service:

- Nominal selalu positif.
- `id_siswa` pada transaksi harus anggota `id_kelas`.
- Satu iuran hanya boleh punya satu transaksi (`unique id_transaksi`).
- Kursi tujuan tidak boleh terisi.
- Jumlah siswa tidak boleh melebihi kapasitas denah.
- Ruangan dengan status `tutup` tidak bisa dipakai (hanya informasi, bukan validasi jadwal).

## 11. Frontend

### 11.1 Struktur file

```text
resources/views/kas/
  index.blade.php        # halaman utama + modal + matriks
  cetak.blade.php        # laporan siap cetak/PDF
resources/views/denah/
  index.blade.php        # tabs tempat duduk + area sekolah
  _drawer-siswa.blade.php
  _drawer-ruangan.blade.php
  _modal-ruangan.blade.php
resources/js/kas.js      # Alpine component kasPage()
resources/js/denah.js    # Alpine component denahPage()
```

`resources/js/app.js`:

```js
import Alpine from 'alpinejs';
import kasPage from './kas';
import denahPage from './denah';

window.Alpine = Alpine;
window.kasPage = kasPage;
window.denahPage = denahPage;

Alpine.start();
```

### 11.2 Wireframe halaman Kas

```text
┌──────────────────────────────────────────────────────────────┐
│ Kas Kelas XII IPA 1        [Kelas ▾]   [Export CSV] [Cetak]  │
├──────────────────────────────────────────────────────────────┤
│ ┌─────────────┐ ┌──────────────────┐ ┌────────────────────┐ │
│ │ Saldo Akhir │ │ Pemasukan Bulan  │ │ Pengeluaran Bulan  │ │
│ │ Rp 1.250.000│ │ Rp 450.000       │ │ Rp 175.000         │ │
│ └─────────────┘ └──────────────────┘ └────────────────────┘ │
├──────────────────────────────────────────────────────────────┤
│ [ Transaksi ] [ Matriks Iuran ]                              │
│ Dari [____] Sampai [____] Jenis [▾] Kategori [▾] Cari [__]  │
│                                              [+ Transaksi]   │
│ Tanggal   Jenis      Kategori    Nominal   Keterangan  Aksi  │
│ 16 Nov    Pemasukan  Iuran       Rp 20.000 Iuran Juli  ⋯     │
│ ...                                                          │
├──────────────────────────────────────────────────────────────┤
│ Matriks: Nama          Jul  Agu  Sep  Okt  Nov  Des          │
│ Ahmad Fauzi            ✅   ✅   ⏳   —    —    —            │
│ Budi Santoso           ✅   ❌   —    —    —    —            │
└──────────────────────────────────────────────────────────────┘
```

Status warna: `belum_bayar` abu-abu, `menunggu` kuning, `terverifikasi` hijau, `ditolak` merah.

### 11.3 State Alpine `kasPage()`

```js
export default function kasPage(initial) {
    return {
        // data
        ringkasan: initial.ringkasan,
        transaksi: initial.transaksi.data,
        pagination: initial.transaksi,
        matriks: initial.matriks,
        daftarKelas: initial.daftarKelas,
        idKelas: initial.idKelas,
        bisaKelola: initial.bisaKelola,
        kategori: initial.kategori,

        // tab & filter
        tab: 'transaksi',
        filter: {
            dari: initial.filter.dari ?? '',
            sampai: initial.filter.sampai ?? '',
            jenis: initial.filter.jenis ?? '',
            kategori: initial.filter.kategori ?? '',
            q: initial.filter.q ?? '',
        },

        // modal
        modalBuka: false,
        modeEdit: false,
        form: { ...initial.formKosong },
        errors: {},
        menyimpan: false,
        memuat: false,
        toast: null,

        init() {
            this.$watch('filter', () => this.muatData(), { deep: true });
            this.$watch('idKelas', () => this.gantiKelas());
        },

        formatRp(nilai) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
            }).format(Number(nilai) || 0);
        },

        async muatData() {
            this.memuat = true;
            try {
                const params = new URLSearchParams({ id_kelas: this.idKelas, ...this.filter });
                const res = await fetch(`/kas/data?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('Gagal memuat data.');
                const data = await res.json();
                this.ringkasan = data.ringkasan;
                this.transaksi = data.transaksi.data;
                this.pagination = data.transaksi;
                this.matriks = data.matriks;
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.memuat = false;
            }
        },

        bukaTambah() { /* reset form, modeEdit=false, modalBuka=true */ },
        bukaEdit(t) { /* isi form dari t, modeEdit=true */ },

        async simpan() {
            this.errors = {};
            this.menyimpan = true;
            const url = this.modeEdit ? `/kas/transaksi/${this.form.id_transaksi}` : '/kas/transaksi';
            const method = this.modeEdit ? 'PUT' : 'POST';
            const body = new FormData();
            // append semua field + _method untuk FormData
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body,
                });
                if (res.status === 422) { this.errors = (await res.json()).errors ?? {}; return; }
                if (!res.ok) throw new Error('Gagal menyimpan.');
                this.modalBuka = false;
                this.showToast('Transaksi tersimpan.');
                await this.muatData();
            } finally { this.menyimpan = false; }
        },

        async batalkan(t) {
            if (!confirm(`Batalkan transaksi "${t.keterangan}"?`)) return;
            const alasan = prompt('Alasan pembatalan:');
            if (!alasan) return;
            // DELETE /kas/transaksi/{id} dengan body alasan_batal
            await this.muatData();
        },

        async verifikasi(iuran) {
            // PATCH /kas/iuran/{id}/verifikasi {status:'terverifikasi'}
            await this.muatData();
        },

        async generateIuran() {
            // POST /kas/iuran/generate {id_kelas, periode, nominal}
            await this.muatData();
        },

        showToast(pesan, tipe = 'success') {
            this.toast = { pesan, tipe };
            setTimeout(() => (this.toast = null), 3000);
        },
    };
}
```

Catatan implementasi: `fetch` untuk `PUT`/`DELETE` dengan FormData mengirim `_method` di body. Semua respons error 422 dipetakan ke `errors` per field.

### 11.4 State Alpine `denahPage()`

```js
export default function denahPage(initial) {
    return {
        tab: 'kursi',
        modeEdit: false,
        bisaKelolaKursi: initial.bisaKelolaKursi,
        bisaKelolaRuangan: initial.bisaKelolaRuangan,

        // kursi
        idKelas: initial.idKelas,
        denah: initial.denah,
        siswa: initial.siswa,
        kursi: initial.kursi,           // [{id_posisi, baris, kolom, siswa:{...}}]
        cariSiswa: '',
        siswaTerpilih: null,
        kursiTerpilih: null,
        drawerSiswa: false,
        kursiSwapPertama: null,

        // ruangan
        ruangan: initial.ruangan,
        gedung: initial.gedung ?? 'Gedung Utama',
        lantai: initial.lantai ?? 1,
        cariRuangan: '',
        ruanganTerpilih: null,
        drawerRuangan: false,
        modalRuangan: false,
        formRuangan: { ...initial.formRuanganKosong },

        init() {
            this.$watch('cariSiswa', () => this.sorotSiswa());
            this.$watch('cariRuangan', () => this.sorotRuangan());
        },

        // ---- kursi ----
        kursiPada(baris, kolom) {
            return this.kursi.find(k => k.baris === baris && k.kolom === kolom) ?? null;
        },

        klikKursi(kursi) {
            if (this.modeEdit && kursi) {
                if (!this.kursiSwapPertama) {
                    this.kursiSwapPertama = kursi;
                    return;
                }
                if (this.kursiSwapPertama.id_posisi === kursi.id_posisi) {
                    this.kursiSwapPertama = null;
                    return;
                }
                this.swap(this.kursiSwapPertama, kursi);
                this.kursiSwapPertama = null;
                return;
            }
            if (kursi) {
                this.siswaTerpilih = kursi.siswa;
                this.kursiTerpilih = kursi;
                this.drawerSiswa = true;
            }
        },

        async swap(a, b) {
            await fetch(`/denah/kelas/${this.idKelas}/swap`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ posisi_a: a.id_posisi, posisi_b: b.id_posisi }),
            });
            await this.muatKursi();
        },

        async acak() {
            if (!confirm('Acak posisi seluruh siswa?')) return;
            await fetch(`/denah/kelas/${this.idKelas}/acak`, { method: 'POST', headers: this.headers() });
            await this.muatKursi();
        },

        async muatKursi() {
            const res = await fetch(`/denah/kelas/${this.idKelas}/data`, { headers: this.headers() });
            const data = await res.json();
            this.denah = data.denah;
            this.kursi = data.kursi;
            this.siswa = data.siswa;
        },

        sorotSiswa() {
            const q = this.cariSiswa.toLowerCase().trim();
            if (!q) return;
            const found = this.kursi.find(k => k.siswa?.nama_siswa?.toLowerCase().includes(q));
            if (found) {
                this.siswaTerpilih = found.siswa;
                this.kursiTerpilih = found;
                this.drawerSiswa = true;
            }
        },

        // ---- ruangan ----
        ruanganTampil() {
            return this.ruangan.filter(r =>
                r.gedung === this.gedung &&
                Number(r.lantai) === Number(this.lantai) &&
                (!this.cariRuangan || r.nama_ruangan.toLowerCase().includes(this.cariRuangan.toLowerCase()))
            );
        },

        sorotRuangan() {
            const q = this.cariRuangan.toLowerCase().trim();
            if (!q) return;
            const found = this.ruangan.find(r => r.nama_ruangan.toLowerCase().includes(q));
            if (found) {
                this.ruanganTerpilih = found;
                this.drawerRuangan = true;
            }
        },

        gayaRuangan(r) {
            const sel = 28; // px per grid unit
            return {
                left: `${r.posisi_x * sel}px`,
                top: `${r.posisi_y * sel}px`,
                width: `${Math.max(1, r.lebar_grid) * sel}px`,
                height: `${Math.max(1, r.tinggi_grid) * sel}px`,
            };
        },

        async simpanRuangan() { /* POST/PUT /denah/ruangan, lalu muatRuangan() */ },
        async ubahStatus(r, status) { /* PATCH /denah/ruangan/{id}/status */ },

        async muatRuangan() {
            const params = new URLSearchParams({ gedung: this.gedung, lantai: this.lantai });
            const res = await fetch(`/denah/sekolah/data?${params}`, { headers: this.headers() });
            this.ruangan = (await res.json()).ruangan;
        },

        headers() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrf(),
            };
        },

        csrf() {
            return document.querySelector('meta[name=csrf-token]').content;
        },
    };
}
```

### 11.5 Komponen Blade utama

**Kas** (`resources/views/kas/index.blade.php`) — ringkas:

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">Kas Kelas</h2>
            <div class="flex gap-2">
                <a href="{{ route('kas.export', request()->query()) }}" class="...">Export CSV</a>
                <a href="{{ route('kas.cetak', request()->query()) }}" target="_blank" class="...">Cetak / PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="kasPage(@js($initial))" x-cloak>
        {{-- summary cards --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <template x-for="kartu in [
                { label: 'Saldo Akhir', nilai: ringkasan.saldo, warna: 'indigo' },
                { label: 'Pemasukan Bulan Ini', nilai: ringkasan.pemasukan_bulan_ini, warna: 'emerald' },
                { label: 'Pengeluaran Bulan Ini', nilai: ringkasan.pengeluaran_bulan_ini, warna: 'rose' },
            ]" :key="kartu.label">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="text-sm text-gray-500" x-text="kartu.label"></p>
                    <p class="mt-2 text-2xl font-bold" :class="`text-${kartu.warna}-600`" x-text="formatRp(kartu.nilai)"></p>
                </div>
            </template>
        </div>

        {{-- tabs --}}
        <div class="mt-6 border-b border-gray-200">
            <button @click="tab='transaksi'" :class="tab==='transaksi' && 'border-indigo-500 text-indigo-600'" class="...">Transaksi</button>
            <button @click="tab='iuran'" :class="tab==='iuran' && 'border-indigo-500 text-indigo-600'" class="...">Matriks Iuran</button>
        </div>

        {{-- filter --}}
        <div x-show="tab==='transaksi'" class="mt-4 grid gap-3 rounded-xl bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <input type="date" x-model="filter.dari" class="rounded-lg border-gray-300">
            <input type="date" x-model="filter.sampai" class="rounded-lg border-gray-300">
            <select x-model="filter.jenis" class="rounded-lg border-gray-300">
                <option value="">Semua jenis</option>
                <option value="pemasukan">Pemasukan</option>
                <option value="pengeluaran">Pengeluaran</option>
            </select>
            <select x-model="filter.kategori" class="rounded-lg border-gray-300">
                <option value="">Semua kategori</option>
                <template x-for="k in kategori" :key="k"><option :value="k" x-text="k"></option></template>
            </select>
            <input type="search" x-model.debounce.300ms="filter.q" placeholder="Cari keterangan..." class="rounded-lg border-gray-300">
        </div>

        {{-- tabel transaksi --}}
        <div x-show="tab==='transaksi'" class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                {{-- thead + tbody x-for transaksi --}}
            </table>
        </div>

        {{-- matriks iuran --}}
        <div x-show="tab==='iuran'" class="mt-4 overflow-x-auto rounded-xl bg-white p-4 shadow-sm">
            {{-- header bulan + baris siswa + chip status --}}
        </div>

        {{-- FAB --}}
        <button x-show="bisaKelola" @click="bukaTambah()"
                class="fixed bottom-6 right-6 rounded-full bg-indigo-600 px-5 py-3 text-white shadow-lg">
            + Transaksi
        </button>

        {{-- modal form --}}
        @include('kas._modal-transaksi')
    </div>
</x-app-layout>
```

**Denah** (`resources/views/denah/index.blade.php`) — ringkas:

```blade
<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Denah Interaktif</h2></x-slot>

    <div class="py-6" x-data="denahPage(@js($initial))" x-cloak>
        <div class="mb-4 flex gap-2">
            <button @click="tab='kursi'" :class="tab==='kursi' && 'bg-indigo-600 text-white'" class="rounded-lg px-4 py-2">Tempat Duduk</button>
            <button @click="tab='sekolah'" :class="tab==='sekolah' && 'bg-indigo-600 text-white'" class="rounded-lg px-4 py-2">Area Sekolah</button>
        </div>

        {{-- ===== TEMPAT DUDUK ===== --}}
        <section x-show="tab==='kursi'" class="rounded-xl bg-white p-4 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <input type="search" x-model.debounce.300ms="cariSiswa" placeholder="Cari nama siswa..." class="rounded-lg border-gray-300">
                <button x-show="bisaKelolaKursi" @click="modeEdit=!modeEdit"
                        :class="modeEdit ? 'bg-amber-500 text-white' : 'bg-gray-100'"
                        class="rounded-lg px-4 py-2">Mode Edit</button>
                <button x-show="bisaKelolaKursi" @click="acak()" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Acak Posisi</button>
            </div>

            {{-- papan tulis --}}
            <div class="mb-4 rounded-lg bg-slate-800 py-3 text-center text-sm font-semibold text-white">PAPAN TULIS</div>

            {{-- meja guru --}}
            <div class="mb-6 flex" :class="{
                'justify-start': denah?.posisi_meja_guru === 'depan_kiri',
                'justify-center': denah?.posisi_meja_guru === 'depan_tengah',
                'justify-end': denah?.posisi_meja_guru === 'depan_kanan',
            }">
                <div class="rounded-lg bg-amber-100 px-6 py-3 text-sm font-medium text-amber-800">Meja Guru</div>
            </div>

            {{-- grid kursi --}}
            <div class="overflow-x-auto pb-4">
                <div class="grid gap-3" :style="`grid-template-columns: repeat(${denah?.jumlah_kolom ?? 4}, minmax(7rem, 1fr))`">
                    <template x-for="baris in (denah?.jumlah_baris ?? 8)" :key="baris">
                        <template x-for="kolom in (denah?.jumlah_kolom ?? 4)" :key="`${baris}-${kolom}`">
                            <button @click="klikKursi(kursiPada(baris, kolom))"
                                    :class="{
                                        'ring-2 ring-indigo-500 bg-indigo-50': siswaTerpilih && kursiPada(baris, kolom)?.siswa?.id_siswa === siswaTerpilih.id_siswa,
                                        'ring-2 ring-amber-400': kursiSwapPertama?.id_posisi === kursiPada(baris, kolom)?.id_posisi,
                                        'bg-gray-50 text-gray-400': !kursiPada(baris, kolom),
                                    }"
                                    class="min-h-[4.5rem] rounded-xl border p-2 text-left text-xs transition hover:border-indigo-400">
                                <span x-show="kursiPada(baris, kolom)?.siswa" x-text="kursiPada(baris, kolom)?.siswa?.nama_siswa" class="font-medium"></span>
                                <span x-show="kursiPada(baris, kolom)?.siswa?.jabatan" x-text="kursiPada(baris, kolom)?.siswa?.jabatan" class="mt-1 block text-[10px] text-indigo-600"></span>
                            </button>
                        </template>
                    </template>
                </div>
            </div>

            <div class="mt-2 text-right text-xs text-gray-500" x-text="`Pintu: ${denah?.posisi_pintu ?? '-'}`"></div>
        </section>

        {{-- ===== AREA SEKOLAH ===== --}}
        <section x-show="tab==='sekolah'" class="rounded-xl bg-white p-4 shadow-sm">
            <div class="mb-4 flex flex-wrap gap-3">
                <select x-model="gedung" @change="muatRuangan()" class="rounded-lg border-gray-300"><!-- gedung --></select>
                <select x-model.number="lantai" @change="muatRuangan()" class="rounded-lg border-gray-300">
                    <template x-for="l in [1,2,3]" :key="l"><option :value="l" x-text="`Lantai ${l}`"></option></template>
                </select>
                <input type="search" x-model.debounce.300ms="cariRuangan" placeholder="Cari ruangan..." class="rounded-lg border-gray-300">
                <button x-show="bisaKelolaRuangan" @click="modalRuangan=true" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">+ Ruangan</button>
            </div>

            <div class="relative h-[520px] min-w-[900px] overflow-auto rounded-xl bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:28px_28px]">
                <template x-for="r in ruanganTampil()" :key="r.id_ruangan">
                    <button @click="ruanganTerpilih=r; drawerRuangan=true"
                            :style="gayaRuangan(r)"
                            :class="{
                                'bg-emerald-50 border-emerald-400': r.status === 'tersedia',
                                'bg-amber-50 border-amber-400': r.status === 'maintenance',
                                'bg-gray-100 border-gray-400': r.status === 'tutup',
                                'ring-2 ring-indigo-500': ruanganTerpilih?.id_ruangan === r.id_ruangan,
                            }"
                            class="absolute rounded-lg border p-2 text-left text-xs shadow-sm transition hover:shadow-md">
                        <span class="block font-semibold" x-text="r.nama_ruangan"></span>
                        <span class="text-[10px] text-gray-500" x-text="r.kode_ruangan"></span>
                    </button>
                </template>
            </div>
        </section>

        {{-- drawer & modal --}}
        @include('denah._drawer-siswa')
        @include('denah._drawer-ruangan')
        @include('denah._modal-ruangan')
    </div>
</x-app-layout>
```

### 11.6 Detail UI/UX

- **Summary cards** memakai `rounded-xl`, `shadow-sm`, aksen warna `indigo`/`emerald`/`rose`, angka Rupiah diformat `Intl.NumberFormat('id-ID')`.
- **Filter** responsif: grid 1 kolom di mobile, 5 kolom di desktop; pencarian memakai debounce 300 ms.
- **Modal transaksi**: `fixed inset-0 z-50 flex items-end sm:items-center`, panel `w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl`, tutup dengan `Esc` dan klik backdrop. Validasi inline dari `errors` 422.
- **Tabel** dibungkus `overflow-x-auto`; di mobile kolom keterangan disembunyikan (`hidden sm:table-cell`).
- **Matriks iuran** horizontal scroll; kolom nama sticky (`sticky left-0 bg-white`); chip status bisa diklik admin/bendahara untuk verifikasi.
- **Denah kursi**: grid minimal 7rem per kursi, horizontal scroll di mobile. Siswa yang dicari diberi `ring-2 ring-indigo-500 bg-indigo-50` dan drawer terbuka otomatis.
- **Mode edit kursi**: klik kursi pertama → ring kuning; klik kursi kedua → swap. Tombol acak meminta konfirmasi.
- **Denah area**: kanvas grid 28px, horizontal scroll di mobile; warna border sesuai status; pencarian membuka drawer ruangan yang cocok.
- **Drawer** di desktop muncul dari kanan (`fixed right-0 top-0 h-full w-full max-w-md`), di mobile dari bawah (`inset-x-0 bottom-0 max-h-[80vh] rounded-t-2xl`).
- **Aksesibilitas**: tombol punya `aria-label`, drawer `role="dialog"`, focus kembali ke pemicu saat ditutup.

## 12. Export & laporan

- CSV: `Content-Type: text/csv; charset=UTF-8`, BOM `\xEF\xBB\xBF` agar Excel membaca UTF-8.
- Nama file: `kas-{nama_kelas}-{YYYYMMDD}.csv`.
- Baris akhir: `TOTAL PEMASUKAN`, `TOTAL PENGELUARAN`, `SALDO`.
- Cetak: halaman `kas/cetak.blade.php` dengan `@media print` menyembunyikan navigasi dan tombol. Bisa disimpan sebagai PDF lewat dialog browser.
- Jika nanti butuh PDF server-side, tinggal tambah `barryvdh/laravel-dompdf` dan route `kas.pdf`; tidak termasuk lingkup awal.

## 13. Rencana pengujian

Feature test (PHPUnit):

1. **Akses kas**
   - Tamu diarahkan ke login.
   - Siswa biasa mendapat 403 saat POST transaksi.
   - Bendahara bisa POST transaksi di kelasnya.
   - Bendahara tidak bisa POST transaksi kelas lain.
   - Admin bisa POST transaksi kelas mana pun.
2. **Ringkasan**
   - Saldo = pemasukan - pengeluaran, transaksi `is_batal` tidak dihitung.
   - Pemasukan/pengeluaran bulan ini hanya menghitung bulan berjalan.
3. **Iuran**
   - Generate membuat satu baris per anggota dan idempoten.
   - Verifikasi membuat tepat satu transaksi dan mengisi `id_transaksi`.
   - Verifikasi kedua menghasilkan 422.
   - Batal transaksi mengembalikan iuran ke `belum_bayar`.
4. **Denah kursi**
   - Swap menukar baris/kolom kedua siswa tanpa melanggar unique.
   - Pindah ke kursi terisi menghasilkan 422.
   - Acak menempatkan setiap anggota tepat satu kali.
   - Ketua kelas bisa mengelola kursi, siswa biasa tidak.
5. **Ruangan**
   - Hanya admin yang bisa CRUD.
   - Filter gedung/lantai mengembalikan data yang sesuai.
6. **Export**
   - CSV berisi header dan baris ringkasan.
   - Siswa tidak bisa mengakses export.

## 14. Urutan implementasi

1. Migrasi baru + model + enum + relasi pendukung.
2. `Akses` service + Gate + form request.
3. `KasController`, `TransaksiKasController`, `IuranKasController`, `ExportKasController`.
4. `DenahController`, `DenahKelasController`, `RuanganSekolahController`.
5. View Kas + Alpine `kas.js` + modal + halaman cetak.
6. View Denah + Alpine `denah.js` + drawer + modal ruangan.
7. Navigasi + perbaikan `Auth::user()->name` → `username` di `layouts/navigation.blade.php`.
8. Seeder demo (tahun ajaran, kelas, siswa, jabatan, transaksi, iuran, denah, ruangan).
9. Feature test.
10. `php artisan migrate --seed`, `npm install && npm run build`, `php artisan test`.

## 15. Keputusan yang saya ambil (bisa dikoreksi)

1. **Nama tabel**: memakai skema Indonesia yang sudah ada; tidak membuat `trnasaksi_kas` baru.
2. **Peta sekolah**: tabel baru `ruangan_sekolah`; `denah_kelas` tetap untuk kursi.
3. **Hak tulis kas**: admin + bendahara (sesuai instruksi Anda). Siswa lain dan orang tua read-only.
4. **Hak kelola kursi**: admin + ketua kelas. Bendahara tidak otomatis bisa mengatur kursi.
5. **Hak kelola ruangan**: admin saja.
6. **Hapus transaksi**: soft-cancel (`is_batal` + alasan), bukan hard delete, agar audit trail aman.
7. **PDF**: CSV + halaman cetak/`window.print()`; tidak menambah dependensi DomPDF.
8. **Filter kas**: endpoint JSON `/kas/data` + Alpine, sehingga ringkasan dan tabel berubah tanpa reload.
9. **Format periode iuran**: `YYYY-MM` (bulanan). Jika sekolah Anda memakai mingguan, kolom `periode` bisa diubah menjadi rentang `YYYY-MM-DD` + `minggu_ke`.
10. **Siswa tidak submit bukti bayar**: karena instruksi Anda menyebut siswa read-only; pencatatan pembayaran dilakukan bendahara/admin.

## 16. Yang belum diputuskan (butuh jawaban Anda)

1. Apakah iuran rutin **bulanan** atau **mingguan**? Desain awal memakai bulanan (`YYYY-MM`).
2. Apakah bendahara boleh melihat/mengelola **semua kelas** atau hanya kelasnya? Desain awal: hanya kelasnya; admin semua kelas.
3. Apakah orang tua boleh melihat **rincian transaksi** kelas anaknya, atau cukup status iuran anaknya? Desain awal: boleh melihat rekap dan transaksi kelas anaknya (read-only).
4. Apakah perlu **upload bukti pembayaran oleh siswa** (status `menunggu`) atau pembayaran selalu dicatat bendahara? Desain awal: dicatat bendahara.
