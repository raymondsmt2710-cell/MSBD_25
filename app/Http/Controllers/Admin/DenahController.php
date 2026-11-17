<?php

namespace App\Http\Controllers\Admin;

use App\Models\DenahKelas;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Fitur 20 — Kelola denah tempat duduk semua kelas.
 *
 * Satu kelas boleh punya beberapa versi denah (riwayat rotasi tempat duduk),
 * tetapi hanya satu yang aktif. posisi_duduk menjamin satu kursi satu siswa
 * (unique id_denah+baris+kolom) dan satu siswa satu kursi (unique id_denah+id_siswa);
 * aplikasi menambah syarat bahwa siswa harus anggota kelas tersebut.
 */
class DenahController extends AdminController
{
    private const MEJA_GURU = ['depan_kiri', 'depan_tengah', 'depan_kanan'];

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
            'berlaku_mulai' => ['required', 'date'],
            'isi' => ['required', Rule::in(['kosong', 'absen', 'acak', 'salin'])],
            'aktifkan' => ['sometimes', 'boolean'],
        ]);

        $siswa = $this->urutanSiswa($kelas, $data['isi']);
        $kursi = $data['jumlah_baris'] * $data['jumlah_kolom'];

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
                'berlaku_mulai' => $data['berlaku_mulai'],
                'dibuat_oleh' => $request->user()->id_user,
                'is_aktif' => $aktifkan,
            ]);

            if ($data['isi'] === 'salin' && $lama) {
                // Salin kursi dari denah aktif sebelumnya; kursi di luar ukuran baru diabaikan.
                $baris = DB::table('posisi_duduk')->where('id_denah', $lama->id_denah)
                    ->where('baris', '<=', $data['jumlah_baris'])->where('kolom', '<=', $data['jumlah_kolom'])
                    ->whereIn('id_siswa', DB::table('anggota_kelas')->select('id_siswa')->where('id_kelas', $kelas->id_kelas))
                    ->get(['id_siswa', 'baris', 'kolom']);
                $this->simpanKursi($denah->id_denah, $baris->map(fn ($p) => (array) $p)->all());
            } elseif (in_array($data['isi'], ['absen', 'acak'], true)) {
                // Diisi baris demi baris dari depan, kiri ke kanan.
                $this->simpanKursi($denah->id_denah, $siswa->values()->map(fn ($id, $i) => [
                    'id_siswa' => $id,
                    'baris' => intdiv($i, $data['jumlah_kolom']) + 1,
                    'kolom' => $i % $data['jumlah_kolom'] + 1,
                ])->all());
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

        $posisi = DB::table('posisi_duduk')->where('id_denah', $denah->id_denah)->get()
            ->mapWithKeys(fn ($p) => ["{$p->baris}-{$p->kolom}" => $p->id_siswa]);

        $belumDuduk = $siswa->whereNotIn('id_siswa', $posisi->values()->all());

        return view('admin.denah.edit', compact('denah', 'kelas', 'siswa', 'posisi', 'belumDuduk'));
    }

    public function simpanPosisi(Request $request, DenahKelas $denah)
    {
        $data = $request->validate([
            'kursi' => ['nullable', 'array'],
            'kursi.*' => ['nullable', 'array'],
            'kursi.*.*' => ['nullable', 'integer'],
        ]);

        $anggota = DB::table('anggota_kelas')->where('id_kelas', $denah->id_kelas)->pluck('id_siswa')->map(fn ($id) => (int) $id)->all();
        $baris = [];

        foreach ($data['kursi'] ?? [] as $b => $kolom) {
            foreach ($kolom as $k => $idSiswa) {
                if ($idSiswa === null) {
                    continue;
                }

                [$b, $k, $idSiswa] = [(int) $b, (int) $k, (int) $idSiswa];

                if ($b < 1 || $b > $denah->jumlah_baris || $k < 1 || $k > $denah->jumlah_kolom) {
                    throw ValidationException::withMessages(['kursi' => 'Ada kursi di luar ukuran denah.']);
                }
                if (! in_array($idSiswa, $anggota, true)) {
                    throw ValidationException::withMessages(['kursi' => 'Ada siswa yang bukan anggota kelas ini.']);
                }

                $baris[] = ['id_siswa' => $idSiswa, 'baris' => $b, 'kolom' => $k];
            }
        }

        $ganda = collect($baris)->countBy('id_siswa')->filter(fn ($n) => $n > 1)->keys();
        if ($ganda->isNotEmpty()) {
            $nama = DB::table('siswa')->whereIn('id_siswa', $ganda)->pluck('nama_siswa')->implode(', ');
            throw ValidationException::withMessages(['kursi' => "Satu siswa hanya boleh menempati satu kursi: {$nama}."]);
        }

        DB::transaction(function () use ($denah, $baris) {
            DB::table('posisi_duduk')->where('id_denah', $denah->id_denah)->delete();
            $this->simpanKursi($denah->id_denah, $baris);
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

        $denah->delete(); // posisi_duduk ikut terhapus (CASCADE)

        return redirect()->route('admin.denah.kelas', $denah->id_kelas)->with('success', 'Denah dihapus.');
    }

    /** Urutan id siswa untuk pengisian otomatis. */
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
}
