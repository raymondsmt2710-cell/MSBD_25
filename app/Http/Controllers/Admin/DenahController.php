<?php

namespace App\Http\Controllers\Admin;

use App\Models\DenahKelas;
use App\Models\Kelas;
use App\Models\PosisiDuduk;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Fitur 20 — Kelola denah tempat duduk semua kelas.
 *
 * Satu kelas boleh punya beberapa versi denah (riwayat rotasi tempat duduk),
 * tetapi hanya satu yang aktif. Satu meja menampung 2 murid (sisi kiri & kanan),
 * sama seperti tampilan sisi siswa: posisi_duduk menjamin satu kursi-sisi satu
 * siswa (unique id_denah+baris+kolom+sisi) dan satu siswa satu kursi
 * (unique id_denah+id_siswa); aplikasi menambah syarat bahwa siswa harus
 * anggota kelas tersebut.
 */
class DenahController extends AdminController
{
    private const MEJA_GURU = ['depan_kiri', 'depan_tengah', 'depan_kanan'];

    private const PINTU = ['kiri', 'kanan', 'belakang'];

    public function index(Request $request)
    {
        $idTahun = $this->idTahunAjaranTerpilih($request->query('tahun'));

        $kelas = DB::table('kelas as k')
            ->leftJoin('denah_kelas as d', fn ($j) => $j->on('d.id_kelas', '=', 'k.id_kelas')->where('d.is_aktif', true))
            ->where('k.id_tahun_ajaran', $idTahun ?? 0)
            ->orderBy('k.tingkat')->orderBy('k.nama_kelas')
            ->select('k.id_kelas', 'k.nama_kelas', 'd.id_denah', 'd.jumlah_baris', 'd.jumlah_kolom', 'd.berlaku_mulai')
            ->selectSub(DB::table('anggota_kelas')->selectRaw('COUNT(*)')->whereColumn('id_kelas', 'k.id_kelas'), 'jumlah_siswa')
            ->selectSub(DB::table('posisi_duduk')->selectRaw('COUNT(*)')->whereColumn('id_denah', 'd.id_denah'), 'terisi')
            ->get();

        return view('admin.denah.index', [
            'kelas' => $kelas,
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'idTahun' => $idTahun,
        ]);
    }

    public function kelas(Kelas $kelas)
    {
        $denah = DB::table('denah_kelas as d')
            ->join('users as u', 'u.id_user', '=', 'd.dibuat_oleh')
            ->where('d.id_kelas', $kelas->id_kelas)
            ->orderByDesc('d.is_aktif')
            ->orderByDesc('d.berlaku_mulai')
            ->select('d.*', 'u.username as pembuat')
            ->selectSub(DB::table('posisi_duduk')->selectRaw('COUNT(*)')->whereColumn('id_denah', 'd.id_denah'), 'terisi')
            ->get();

        $jumlahSiswa = DB::table('anggota_kelas')->where('id_kelas', $kelas->id_kelas)->count();

        return view('admin.denah.kelas', compact('kelas', 'denah', 'jumlahSiswa') + ['mejaGuru' => self::MEJA_GURU]);
    }

    public function store(Request $request, Kelas $kelas)
    {
        $data = $request->validate([
            'jumlah_baris' => ['required', 'integer', 'min:1', 'max:15'],
            'jumlah_kolom' => ['required', 'integer', 'min:1', 'max:15'],
            'posisi_meja_guru' => ['required', Rule::in(self::MEJA_GURU)],
            'posisi_pintu' => ['nullable', Rule::in(self::PINTU)],
            'berlaku_mulai' => ['required', 'date'],
            'isi' => ['required', Rule::in(['kosong', 'absen', 'acak', 'salin'])],
            'aktifkan' => ['sometimes', 'boolean'],
        ]);

        $siswa = $this->urutanSiswa($kelas, $data['isi']);

        // Satu meja = 2 murid (sisi kiri & kanan), jadi kapasitas = baris × kolom × 2.
        $kursi = $data['jumlah_baris'] * $data['jumlah_kolom'] * count(PosisiDuduk::SISI);

        if ($data['isi'] !== 'kosong' && $data['isi'] !== 'salin' && $siswa->count() > $kursi) {
            throw ValidationException::withMessages([
                'jumlah_baris' => "Kursi ({$kursi}) kurang dari jumlah siswa ({$siswa->count()}). Tambah baris atau kolom.",
            ]);
        }

        $denah = DB::transaction(function () use ($kelas, $data, $siswa, $request) {
            $aktifkan = $request->boolean('aktifkan')
                || ! DenahKelas::where('id_kelas', $kelas->id_kelas)->where('is_aktif', true)->exists();

            $lama = DenahKelas::where('id_kelas', $kelas->id_kelas)->where('is_aktif', true)->first();

            if ($aktifkan) {
                DenahKelas::where('id_kelas', $kelas->id_kelas)->update(['is_aktif' => false]);
            }

            $denah = DenahKelas::create([
                'id_kelas' => $kelas->id_kelas,
                'jumlah_baris' => $data['jumlah_baris'],
                'jumlah_kolom' => $data['jumlah_kolom'],
                'posisi_meja_guru' => $data['posisi_meja_guru'],
                'posisi_pintu' => $data['posisi_pintu'] ?? 'kanan',
                'berlaku_mulai' => $data['berlaku_mulai'],
                'dibuat_oleh' => $request->user()->id_user,
                'is_aktif' => $aktifkan,
            ]);

            if ($data['isi'] === 'salin' && $lama) {
                // Salin kursi dari denah aktif sebelumnya, termasuk sisinya;
                // kursi di luar ukuran baru diabaikan.
                $baris = DB::table('posisi_duduk')->where('id_denah', $lama->id_denah)
                    ->where('baris', '<=', $data['jumlah_baris'])->where('kolom', '<=', $data['jumlah_kolom'])
                    ->whereIn('id_siswa', DB::table('anggota_kelas')->select('id_siswa')->where('id_kelas', $kelas->id_kelas))
                    ->get(['id_siswa', 'baris', 'kolom', 'sisi']);
                $this->simpanKursi($denah->id_denah, $baris->map(fn ($p) => (array) $p)->all());
            } elseif (in_array($data['isi'], ['absen', 'acak'], true)) {
                // Diisi meja demi meja dari depan: sisi kiri dulu, lalu sisi kanan.
                $this->simpanKursi($denah->id_denah, $this->kursiBerurutan($siswa->values()->all(), $data['jumlah_baris'], $data['jumlah_kolom']));
            }

            return $denah;
        });

        return redirect()->route('admin.denah.edit', $denah->id_denah)->with('success', 'Denah dibuat. Atur kursi bila perlu.');
    }

    public function edit(DenahKelas $denah)
    {
        $kelas = Kelas::findOrFail($denah->id_kelas);

        $siswa = DB::table('anggota_kelas as ak')
            ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
            ->where('ak.id_kelas', $kelas->id_kelas)
            ->orderByRaw('ak.no_absen IS NULL, ak.no_absen')->orderBy('s.nama_siswa')
            ->get(['s.id_siswa', 's.nama_siswa', 'ak.no_absen']);

        // Satu meja punya dua sisi, jadi kuncinya WAJIB menyertakan sisi.
        // Tanpa sisi, murid kedua di meja yang sama akan menimpa yang pertama.
        $posisi = DB::table('posisi_duduk')->where('id_denah', $denah->id_denah)->get()
            ->mapWithKeys(fn ($p) => ["{$p->baris}-{$p->kolom}-".($p->sisi ?: 'kiri') => $p->id_siswa]);

        $belumDuduk = $siswa->whereNotIn('id_siswa', $posisi->values()->all());

        return view('admin.denah.edit', [
            'denah' => $denah,
            'kelas' => $kelas,
            'siswa' => $siswa,
            'posisi' => $posisi,
            'belumDuduk' => $belumDuduk,
            'sisi' => PosisiDuduk::SISI,
        ]);
    }

    public function simpanPosisi(Request $request, DenahKelas $denah)
    {
        $data = $request->validate([
            'kursi' => ['nullable', 'array'],
            'kursi.*' => ['nullable', 'array'],
            'kursi.*.*' => ['nullable', 'array'],
            'kursi.*.*.*' => ['nullable', 'integer'],
        ]);

        $anggota = DB::table('anggota_kelas')->where('id_kelas', $denah->id_kelas)->pluck('id_siswa')->map(fn ($id) => (int) $id)->all();
        $baris = [];

        foreach ($data['kursi'] ?? [] as $b => $kolom) {
            foreach ($kolom as $k => $sisiMeja) {
                foreach ($sisiMeja as $sisi => $idSiswa) {
                    if ($idSiswa === null) {
                        continue;
                    }

                    [$b, $k, $idSiswa] = [(int) $b, (int) $k, (int) $idSiswa];
                    $sisi = in_array($sisi, PosisiDuduk::SISI, true) ? $sisi : 'kiri';

                    if ($b < 1 || $b > $denah->jumlah_baris || $k < 1 || $k > $denah->jumlah_kolom) {
                        throw ValidationException::withMessages(['kursi' => 'Ada kursi di luar ukuran denah.']);
                    }
                    if (! in_array($idSiswa, $anggota, true)) {
                        throw ValidationException::withMessages(['kursi' => 'Ada siswa yang bukan anggota kelas ini.']);
                    }

                    $baris[] = ['id_siswa' => $idSiswa, 'baris' => $b, 'kolom' => $k, 'sisi' => $sisi];
                }
            }
        }

        $ganda = collect($baris)->countBy('id_siswa')->filter(fn ($n) => $n > 1)->keys();
        if ($ganda->isNotEmpty()) {
            $nama = DB::table('siswa')->whereIn('id_siswa', $ganda)->pluck('nama_siswa')->implode(', ');
            throw ValidationException::withMessages(['kursi' => "Satu siswa hanya boleh menempati satu kursi: {$nama}."]);
        }

        $gandaSisi = collect($baris)
            ->countBy(fn ($p) => "{$p['baris']}-{$p['kolom']}-{$p['sisi']}")
            ->filter(fn ($n) => $n > 1)
            ->keys();
        if ($gandaSisi->isNotEmpty()) {
            throw ValidationException::withMessages(['kursi' => 'Ada sisi meja yang diisi lebih dari satu siswa.']);
        }

        DB::transaction(function () use ($denah, $baris) {
            $this->terapkanPosisi($denah->id_denah, $baris);
        });

        return back()->with('success', 'Posisi duduk disimpan ('.count($baris).' kursi terisi).');
    }

    public function aktifkan(DenahKelas $denah)
    {
        DB::transaction(function () use ($denah) {
            DenahKelas::where('id_kelas', $denah->id_kelas)->update(['is_aktif' => false]);
            $denah->update(['is_aktif' => true]);
        });

        return back()->with('success', 'Denah ini sekarang menjadi denah aktif kelas.');
    }

    public function destroy(DenahKelas $denah)
    {
        if ($denah->is_aktif) {
            return back()->with('error', 'Denah aktif tidak dapat dihapus. Aktifkan denah lain terlebih dahulu.');
        }

        $denah->delete();

        return redirect()->route('admin.denah.kelas', $denah->id_kelas)->with('success', 'Denah dihapus.');
    }


    private function urutanSiswa(Kelas $kelas, string $mode)
    {
        $q = DB::table('anggota_kelas as ak')
            ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
            ->where('ak.id_kelas', $kelas->id_kelas)
            ->where('s.status', 'aktif');

        return $mode === 'acak'
            ? $q->pluck('s.id_siswa')->shuffle()
            : $q->orderByRaw('ak.no_absen IS NULL, ak.no_absen')->orderBy('s.nama_siswa')->pluck('s.id_siswa');
    }

    private function simpanKursi(int $idDenah, array $baris): void
    {
        if ($baris) {
            DB::table('posisi_duduk')->insert(array_map(fn ($p) => $p + ['id_denah' => $idDenah], $baris));
        }
    }

    /**
     * Terapkan susunan kursi TANPA menghapus lalu menulis ulang semuanya.
     *
     * `id_posisi` dipakai sebagai rujukan oleh fitur pindah & tukar posisi di
     * sisi siswa, jadi baris yang tidak berubah harus mempertahankan id-nya.
     * Yang dilakukan:
     *   1. Kursi yang dikosongkan -> dihapus.
     *   2. Siswa yang sudah punya kursi -> dipindahkan (id tetap).
     *   3. Siswa yang belum punya kursi -> ditambahkan.
     *
     * Urutan langkah penting: kursi yang akan ditempati dikosongkan lebih dulu
     * agar tidak menabrak unique (id_denah, baris, kolom, sisi).
     *
     * @param  array<int, array<string, mixed>>  $baris
     */
    private function terapkanPosisi(int $idDenah, array $baris): void
    {
        $lama = DB::table('posisi_duduk')->where('id_denah', $idDenah)->get();
        $idSiswaBaru = collect($baris)->pluck('id_siswa')->map(fn ($id) => (int) $id)->all();

        // 1. Kursi yang dikosongkan.
        $hapus = $lama->pluck('id_siswa')->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => in_array($id, $idSiswaBaru, true))->all();

        if ($hapus) {
            DB::table('posisi_duduk')->where('id_denah', $idDenah)->whereIn('id_siswa', $hapus)->delete();
        }

        $perSiswa = $lama->keyBy(fn ($p) => (int) $p->id_siswa);

        // 2a. Tentukan baris yang benar-benar berpindah.
        $pindah = [];

        foreach ($baris as $p) {
            $idSiswa = (int) $p['id_siswa'];
            $sekarang = $perSiswa->get($idSiswa);

            if (! $sekarang) {
                continue; // siswa baru, ditangani di langkah 3
            }

            if ((int) $sekarang->baris === (int) $p['baris']
                && (int) $sekarang->kolom === (int) $p['kolom']
                && $sekarang->sisi === $p['sisi']) {
                continue; // tidak berubah, id_posisi tetap
            }

            $pindah[] = ['id_posisi' => $sekarang->id_posisi, 'tujuan' => $p];
        }

        // 2b. Parkir dulu di luar grid agar tidak menabrak unique
        // (id_denah, baris, kolom, sisi) saat dua murid bertukar tempat.
        // baris/kolom bertipe tinyint unsigned, jadi parkir memakai baris 0
        // dan kolom yang berbeda-beda (0, 1, 2, ...) supaya tidak saling bentrok.
        foreach ($pindah as $i => $item) {
            DB::table('posisi_duduk')->where('id_posisi', $item['id_posisi'])
                ->update(['baris' => 0, 'kolom' => $i]);
        }

        // 2c. Baru pindahkan ke posisi akhir.
        foreach ($pindah as $item) {
            DB::table('posisi_duduk')->where('id_posisi', $item['id_posisi'])->update([
                'baris' => $item['tujuan']['baris'],
                'kolom' => $item['tujuan']['kolom'],
                'sisi' => $item['tujuan']['sisi'],
            ]);
        }

        // 3. Siswa yang belum punya kursi.
        $baru = array_values(array_filter(
            $baris,
            fn ($p) => ! $perSiswa->has((int) $p['id_siswa']),
        ));

        $this->simpanKursi($idDenah, $baru);
    }

    /**
     * Susun id siswa ke kursi meja demi meja: sisi kiri dulu, lalu sisi kanan.
     * Satu meja menampung 2 murid, sama seperti tampilan sisi siswa.
     *
     * @param  array<int, int>  $idSiswa
     * @return array<int, array<string, mixed>>
     */
    private function kursiBerurutan(array $idSiswa, int $jumlahBaris, int $jumlahKolom): array
    {
        $baris = [];
        $index = 0;

        for ($b = 1; $b <= $jumlahBaris; $b++) {
            for ($k = 1; $k <= $jumlahKolom; $k++) {
                foreach (PosisiDuduk::SISI as $sisi) {
                    if (! isset($idSiswa[$index])) {
                        return $baris;
                    }

                    $baris[] = [
                        'id_siswa' => $idSiswa[$index],
                        'baris' => $b,
                        'kolom' => $k,
                        'sisi' => $sisi,
                    ];

                    $index++;
                }
            }
        }

        return $baris;
    }
}
