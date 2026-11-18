<?php

namespace App\Http\Controllers\Admin;

use App\Models\AnggotaKelas;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Fitur 8 — Kelola kelas dan wali kelas.
 * Fitur 9 — Penempatan siswa ke kelas (roster kelas digital).
 *
 * Aturan yang dijaga:
 *  - nama kelas unik dalam satu tahun ajaran;
 *  - seorang guru menjadi wali paling banyak satu kelas per tahun ajaran;
 *  - seorang siswa hanya berada di satu kelas per tahun ajaran;
 *  - nomor absen unik dalam satu kelas.
 */
class KelasController extends AdminController
{
    public function index(Request $request)
    {
        $idTahun = $this->idTahunAjaranTerpilih($request->query('tahun'));

        $kelas = DB::table('kelas as k')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'k.id_wali_kelas')
            ->where('k.id_tahun_ajaran', $idTahun ?? 0)
            ->select('k.*', 'g.nama_guru as wali')
            ->selectSub(DB::table('anggota_kelas')->selectRaw('COUNT(*)')->whereColumn('id_kelas', 'k.id_kelas'), 'jumlah_siswa')
            ->selectSub(DB::table('jadwal_pelajaran')->selectRaw('COUNT(*)')->whereColumn('id_kelas', 'k.id_kelas'), 'jumlah_jadwal')
            ->orderBy('k.tingkat')
            ->orderBy('k.nama_kelas')
            ->get();

        $tahunAjaran = TahunAjaran::orderByDesc('tanggal_mulai')->get();

        return view('admin.kelas.index', compact('kelas', 'tahunAjaran', 'idTahun'));
    }

    public function create(Request $request)
    {
        $kelas = new Kelas(['id_tahun_ajaran' => $this->idTahunAjaranTerpilih($request->query('tahun')), 'tingkat' => 10]);

        return view('admin.kelas.form', $this->dataForm($kelas));
    }

    public function store(Request $request)
    {
        $kelas = Kelas::create($this->validasi($request));

        return redirect()->route('admin.kelas.show', $kelas->id_kelas)
            ->with('success', "Kelas {$kelas->nama_kelas} dibuat. Tambahkan siswanya di bawah.");
    }

    public function show(Kelas $kelas)
    {
        $tahun = TahunAjaran::find($kelas->id_tahun_ajaran);
        $wali = DB::table('guru')->where('id_guru', $kelas->id_wali_kelas)->value('nama_guru');

        $anggota = DB::table('anggota_kelas as ak')
            ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
            ->where('ak.id_kelas', $kelas->id_kelas)
            ->orderByRaw('ak.no_absen IS NULL, ak.no_absen')
            ->orderBy('s.nama_siswa')
            ->get(['ak.id_anggota', 'ak.no_absen', 's.id_siswa', 's.nis', 's.nama_siswa', 's.jenis_kelamin', 's.status']);

        // Kandidat penempatan: siswa aktif yang belum punya kelas di tahun ajaran ini.
        $belumBerkelas = DB::table('siswa as s')
            ->where('s.status', 'aktif')
            ->whereNotExists(function ($q) use ($kelas) {
                $q->from('anggota_kelas as ak')
                    ->join('kelas as k', 'k.id_kelas', '=', 'ak.id_kelas')
                    ->whereColumn('ak.id_siswa', 's.id_siswa')
                    ->where('k.id_tahun_ajaran', $kelas->id_tahun_ajaran);
            })
            ->orderBy('s.nama_siswa')
            ->get(['s.id_siswa', 's.nis', 's.nama_siswa']);

        return view('admin.kelas.show', compact('kelas', 'tahun', 'wali', 'anggota', 'belumBerkelas'));
    }

    public function edit(Kelas $kelas)
    {
        return view('admin.kelas.form', $this->dataForm($kelas));
    }

    public function update(Request $request, Kelas $kelas)
    {
        $data = $this->validasi($request, $kelas);

        if ((int) $data['id_tahun_ajaran'] !== (int) $kelas->id_tahun_ajaran && $this->punyaIsi($kelas)) {
            return back()->withInput()->with('error', 'Tahun ajaran tidak dapat dipindah karena kelas sudah berisi siswa, jadwal, atau kas.');
        }

        $kelas->update($data);

        return redirect()->route('admin.kelas.show', $kelas->id_kelas)->with('success', 'Data kelas diperbarui.');
    }

    public function destroy(Kelas $kelas)
    {
        // FK kelas → anggota/jadwal/kas/denah berjenis CASCADE: menghapus kelas yang berisi
        // akan ikut menghapus riwayat presensi dan kas. Karena itu hanya kelas kosong yang boleh dihapus.
        if ($this->punyaIsi($kelas)) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena sudah berisi siswa, jadwal, atau transaksi kas.');
        }

        $kelas->delete();

        return redirect()->route('admin.kelas.index', ['tahun' => $kelas->id_tahun_ajaran])
            ->with('success', "Kelas {$kelas->nama_kelas} dihapus.");
    }

    // ------------------------------------------------------------------
    // Fitur 9 — anggota kelas
    // ------------------------------------------------------------------

    public function tambahAnggota(Request $request, Kelas $kelas)
    {
        $data = $request->validate([
            'id_siswa' => ['required', 'array', 'min:1'],
            'id_siswa.*' => ['integer', 'distinct', Rule::exists('siswa', 'id_siswa')->where('status', 'aktif')],
        ], ['id_siswa.*.exists' => 'Hanya siswa berstatus aktif yang dapat ditempatkan.']);

        $jumlah = DB::transaction(function () use ($kelas, $data) {
            // Kunci kelas lain di tahun ajaran yang sama agar dua admin tidak menempatkan
            // siswa yang sama ke dua kelas berbeda secara bersamaan.
            $sudahBerkelas = DB::table('anggota_kelas as ak')
                ->join('kelas as k', 'k.id_kelas', '=', 'ak.id_kelas')
                ->where('k.id_tahun_ajaran', $kelas->id_tahun_ajaran)
                ->whereIn('ak.id_siswa', $data['id_siswa'])
                ->lockForUpdate()
                ->pluck('ak.id_siswa');

            if ($sudahBerkelas->isNotEmpty()) {
                $nama = DB::table('siswa')->whereIn('id_siswa', $sudahBerkelas)->pluck('nama_siswa')->implode(', ');
                throw ValidationException::withMessages(['id_siswa' => "Sudah punya kelas pada tahun ajaran ini: {$nama}."]);
            }

            $nomor = (int) DB::table('anggota_kelas')->where('id_kelas', $kelas->id_kelas)->max('no_absen');

            // Nomor absen lanjutan mengikuti urutan nama siswa yang ditambahkan.
            $siswa = DB::table('siswa')->whereIn('id_siswa', $data['id_siswa'])->orderBy('nama_siswa')->pluck('id_siswa');

            DB::table('anggota_kelas')->insert($siswa->map(fn ($id) => [
                'id_kelas' => $kelas->id_kelas,
                'id_siswa' => $id,
                'no_absen' => min(++$nomor, 255),
            ])->all());

            return $siswa->count();
        });

        return back()->with('success', "{$jumlah} siswa ditempatkan ke {$kelas->nama_kelas}.");
    }

    public function simpanNomorAbsen(Request $request, Kelas $kelas)
    {
        $data = $request->validate([
            'no_absen' => ['required', 'array'],
            'no_absen.*' => ['nullable', 'integer', 'min:1', 'max:255'],
        ]);

        $nomor = collect($data['no_absen'])->filter(fn ($n) => $n !== null);
        if ($nomor->count() !== $nomor->unique()->count()) {
            return back()->with('error', 'Nomor absen tidak boleh ganda dalam satu kelas.');
        }

        DB::transaction(function () use ($kelas, $data) {
            foreach ($data['no_absen'] as $idAnggota => $no) {
                DB::table('anggota_kelas')
                    ->where('id_kelas', $kelas->id_kelas)   // id_anggota dari kelas lain diabaikan
                    ->where('id_anggota', (int) $idAnggota)
                    ->update(['no_absen' => $no]);
            }
        });

        return back()->with('success', 'Nomor absen disimpan.');
    }

    public function urutkanAbsen(Kelas $kelas)
    {
        DB::transaction(function () use ($kelas) {
            $urut = DB::table('anggota_kelas as ak')
                ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
                ->where('ak.id_kelas', $kelas->id_kelas)
                ->orderBy('s.nama_siswa')
                ->pluck('ak.id_anggota');

            foreach ($urut as $i => $idAnggota) {
                DB::table('anggota_kelas')->where('id_anggota', $idAnggota)->update(['no_absen' => $i + 1]);
            }
        });

        return back()->with('success', 'Nomor absen diurutkan ulang berdasarkan nama.');
    }

    public function hapusAnggota(Kelas $kelas, int $anggota)
    {
        $baris = AnggotaKelas::where('id_kelas', $kelas->id_kelas)->findOrFail($anggota);

        DB::transaction(function () use ($kelas, $baris) {
            // Jabatan dan kursi di kelas ini ikut dilepas; riwayat presensi tetap tersimpan.
            DB::table('pengurus_kelas')->where('id_kelas', $kelas->id_kelas)->where('id_siswa', $baris->id_siswa)->delete();
            DB::table('posisi_duduk')
                ->whereIn('id_denah', DB::table('denah_kelas')->select('id_denah')->where('id_kelas', $kelas->id_kelas))
                ->where('id_siswa', $baris->id_siswa)
                ->delete();
            $baris->delete();
        });

        return back()->with('success', 'Siswa dikeluarkan dari kelas. Jabatan dan kursinya di kelas ini ikut dilepas.');
    }

    // ------------------------------------------------------------------

    private function dataForm(Kelas $kelas): array
    {
        return [
            'kelas' => $kelas,
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'guru' => DB::table('guru as g')->join('users as u', 'u.id_user', '=', 'g.id_user')
                ->where('u.is_active', true)->orderBy('g.nama_guru')->get(['g.id_guru', 'g.nama_guru', 'g.nip']),
        ];
    }

    private function punyaIsi(Kelas $kelas): bool
    {
        return DB::table('anggota_kelas')->where('id_kelas', $kelas->id_kelas)->exists()
            || DB::table('jadwal_pelajaran')->where('id_kelas', $kelas->id_kelas)->exists()
            || DB::table('transaksi_kas')->where('id_kelas', $kelas->id_kelas)->exists();
    }

    private function validasi(Request $request, ?Kelas $kelas = null): array
    {
        $idTahun = $request->input('id_tahun_ajaran');
        $abaikan = $kelas?->id_kelas;

        return $request->validate([
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'nama_kelas' => ['required', 'string', 'max:20',
                Rule::unique('kelas', 'nama_kelas')->where('id_tahun_ajaran', $idTahun)->ignore($abaikan, 'id_kelas')],
            'tingkat' => ['required', 'integer', Rule::in([10, 11, 12])],
            'jurusan' => ['nullable', 'string', 'max:30'],
            'ruangan' => ['nullable', 'string', 'max:20'],
            'id_wali_kelas' => ['nullable', 'integer', 'exists:guru,id_guru',
                Rule::unique('kelas', 'id_wali_kelas')->where('id_tahun_ajaran', $idTahun)->ignore($abaikan, 'id_kelas')],
        ], [
            'nama_kelas.unique' => 'Nama kelas sudah dipakai pada tahun ajaran ini.',
            'id_wali_kelas.unique' => 'Guru ini sudah menjadi wali kelas lain pada tahun ajaran yang sama.',
        ]);
    }
}
