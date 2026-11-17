<?php

namespace App\Http\Controllers\Admin;

use App\Models\Kelas;
use App\Models\Organisasi;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Fitur 12 — Tetapkan pengurus kelas dan pengurus organisasi.
 *
 * Penetapan bendahara kelas menentukan siapa yang boleh mencatat kas kelas, jadi
 * aturan di sini dijaga ketat:
 *  - jabatan harus berlingkup sesuai (kelas / organisasi);
 *  - pengurus kelas harus anggota kelas tersebut;
 *  - jumlah pemegang tidak melebihi kuota jabatan;
 *  - satu siswa memegang paling banyak satu jabatan per kelas, atau per organisasi per tahun ajaran.
 * Pemeriksaan kuota dilakukan di dalam transaksi dengan lock agar dua penetapan
 * bersamaan tidak sama-sama lolos.
 */
class PengurusController extends AdminController
{
    public function index(Request $request)
    {
        $idTahun = $this->idTahunAjaranTerpilih($request->query('tahun'));

        $kelas = DB::table('kelas as k')
            ->where('k.id_tahun_ajaran', $idTahun ?? 0)
            ->select('k.id_kelas', 'k.nama_kelas')
            ->selectSub(DB::table('pengurus_kelas')->selectRaw('COUNT(*)')->whereColumn('id_kelas', 'k.id_kelas'), 'jumlah_pengurus')
            ->selectSub(DB::table('pengurus_kelas as pk')->join('jabatan as j', 'j.id_jabatan', '=', 'pk.id_jabatan')
                ->join('siswa as s', 's.id_siswa', '=', 'pk.id_siswa')
                ->selectRaw("GROUP_CONCAT(s.nama_siswa SEPARATOR ', ')")
                ->whereColumn('pk.id_kelas', 'k.id_kelas')->where('j.nama_jabatan', 'like', '%bendahara%'), 'bendahara')
            ->orderBy('k.tingkat')->orderBy('k.nama_kelas')
            ->get();

        $organisasi = DB::table('organisasi as o')
            ->select('o.id_organisasi', 'o.nama_organisasi', 'o.singkatan')
            ->selectSub(DB::table('pengurus_organisasi')->selectRaw('COUNT(*)')
                ->whereColumn('id_organisasi', 'o.id_organisasi')->where('id_tahun_ajaran', $idTahun ?? 0), 'jumlah_pengurus')
            ->orderBy('o.nama_organisasi')
            ->get();

        $tahunAjaran = TahunAjaran::orderByDesc('tanggal_mulai')->get();

        return view('admin.pengurus.index', compact('kelas', 'organisasi', 'tahunAjaran', 'idTahun'));
    }

    // ------------------------------------------------------------------ kelas

    public function kelas(Kelas $kelas)
    {
        $jabatan = DB::table('jabatan')->where('lingkup', 'kelas')->orderBy('urutan')->orderBy('nama_jabatan')->get();

        $pengurus = DB::table('pengurus_kelas as pk')
            ->join('siswa as s', 's.id_siswa', '=', 'pk.id_siswa')
            ->where('pk.id_kelas', $kelas->id_kelas)
            ->get(['pk.id_pengurus_kelas as id', 'pk.id_jabatan', 's.nama_siswa', 's.nis'])
            ->groupBy('id_jabatan');

        $calon = DB::table('anggota_kelas as ak')
            ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
            ->where('ak.id_kelas', $kelas->id_kelas)
            ->where('s.status', 'aktif')
            ->whereNotIn('s.id_siswa', DB::table('pengurus_kelas')->select('id_siswa')->where('id_kelas', $kelas->id_kelas))
            ->orderBy('s.nama_siswa')
            ->get(['s.id_siswa', 's.nama_siswa', 's.nis']);

        return view('admin.pengurus.form', [
            'judul' => 'Pengurus '.$kelas->nama_kelas,
            'subjudul' => 'Hanya anggota kelas ini yang dapat ditetapkan. Bendahara kelas berhak mencatat kas kelas.',
            'jabatan' => $jabatan,
            'pengurus' => $pengurus,
            'calon' => $calon,
            'aksiSimpan' => route('admin.pengurus.kelas.simpan', $kelas->id_kelas),
            'ruteHapus' => fn ($id) => route('admin.pengurus.kelas.hapus', [$kelas->id_kelas, $id]),
            'kembali' => route('admin.pengurus.index', ['tahun' => $kelas->id_tahun_ajaran]),
            'pakaiBidang' => false,
        ]);
    }

    public function simpanKelas(Request $request, Kelas $kelas)
    {
        $data = $request->validate([
            'id_jabatan' => ['required', 'integer', Rule::exists('jabatan', 'id_jabatan')->where('lingkup', 'kelas')],
            'id_siswa' => ['required', 'integer',
                Rule::exists('anggota_kelas', 'id_siswa')->where('id_kelas', $kelas->id_kelas),
                Rule::unique('pengurus_kelas', 'id_siswa')->where('id_kelas', $kelas->id_kelas)],
        ], [
            'id_jabatan.exists' => 'Jabatan tidak ditemukan atau bukan jabatan kelas.',
            'id_siswa.exists' => 'Siswa tersebut bukan anggota kelas ini.',
            'id_siswa.unique' => 'Siswa tersebut sudah memegang jabatan lain di kelas ini.',
        ]);

        DB::transaction(function () use ($kelas, $data) {
            $terisi = DB::table('pengurus_kelas')
                ->where('id_kelas', $kelas->id_kelas)->where('id_jabatan', $data['id_jabatan'])
                ->lockForUpdate()->count();
            $this->cekKuota((int) $data['id_jabatan'], $terisi);

            DB::table('pengurus_kelas')->insert($data + ['id_kelas' => $kelas->id_kelas]);
        });

        return back()->with('success', 'Pengurus kelas ditetapkan.');
    }

    public function hapusKelas(Kelas $kelas, int $pengurus)
    {
        $hapus = DB::table('pengurus_kelas')->where('id_kelas', $kelas->id_kelas)->where('id_pengurus_kelas', $pengurus)->delete();
        abort_unless($hapus > 0, 404);

        return back()->with('success', 'Jabatan dilepas.');
    }

    // ------------------------------------------------------------------ organisasi

    public function organisasi(Request $request, Organisasi $organisasi)
    {
        $idTahun = $this->idTahunAjaranTerpilih($request->query('tahun'));
        abort_if($idTahun === null, 404, 'Belum ada tahun ajaran.');

        $jabatan = DB::table('jabatan')->where('lingkup', 'organisasi')->orderBy('urutan')->orderBy('nama_jabatan')->get();

        $pengurus = DB::table('pengurus_organisasi as po')
            ->join('siswa as s', 's.id_siswa', '=', 'po.id_siswa')
            ->where('po.id_organisasi', $organisasi->id_organisasi)
            ->where('po.id_tahun_ajaran', $idTahun)
            ->get(['po.id_pengurus_org as id', 'po.id_jabatan', 'po.bidang', 's.nama_siswa', 's.nis'])
            ->groupBy('id_jabatan');

        $calon = DB::table('siswa as s')
            ->where('s.status', 'aktif')
            ->whereNotIn('s.id_siswa', DB::table('pengurus_organisasi')->select('id_siswa')
                ->where('id_organisasi', $organisasi->id_organisasi)->where('id_tahun_ajaran', $idTahun))
            ->orderBy('s.nama_siswa')
            ->get(['s.id_siswa', 's.nama_siswa', 's.nis']);

        $tahun = TahunAjaran::find($idTahun);

        return view('admin.pengurus.form', [
            'judul' => 'Pengurus '.($organisasi->singkatan ?: $organisasi->nama_organisasi),
            'subjudul' => $organisasi->nama_organisasi.' · Tahun ajaran '.$tahun->nama,
            'jabatan' => $jabatan,
            'pengurus' => $pengurus,
            'calon' => $calon,
            'aksiSimpan' => route('admin.pengurus.organisasi.simpan', [$organisasi->id_organisasi, 'tahun' => $idTahun]),
            'ruteHapus' => fn ($id) => route('admin.pengurus.organisasi.hapus', [$organisasi->id_organisasi, $id]),
            'kembali' => route('admin.pengurus.index', ['tahun' => $idTahun]),
            'pakaiBidang' => true,
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'idTahun' => $idTahun,
        ]);
    }

    public function simpanOrganisasi(Request $request, Organisasi $organisasi)
    {
        $idTahun = $this->idTahunAjaranTerpilih($request->query('tahun'));
        abort_unless($idTahun && TahunAjaran::whereKey($idTahun)->exists(), 404);

        $data = $request->validate([
            'id_jabatan' => ['required', 'integer', Rule::exists('jabatan', 'id_jabatan')->where('lingkup', 'organisasi')],
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')->where('status', 'aktif'),
                Rule::unique('pengurus_organisasi', 'id_siswa')
                    ->where('id_organisasi', $organisasi->id_organisasi)->where('id_tahun_ajaran', $idTahun)],
            'bidang' => ['nullable', 'string', 'max:50'],
        ], [
            'id_jabatan.exists' => 'Jabatan tidak ditemukan atau bukan jabatan organisasi.',
            'id_siswa.unique' => 'Siswa tersebut sudah memegang jabatan lain di organisasi ini pada tahun ajaran yang sama.',
        ]);

        DB::transaction(function () use ($organisasi, $idTahun, $data) {
            $terisi = DB::table('pengurus_organisasi')
                ->where('id_organisasi', $organisasi->id_organisasi)
                ->where('id_tahun_ajaran', $idTahun)
                ->where('id_jabatan', $data['id_jabatan'])
                ->lockForUpdate()->count();
            $this->cekKuota((int) $data['id_jabatan'], $terisi);

            DB::table('pengurus_organisasi')->insert($data + [
                'id_organisasi' => $organisasi->id_organisasi,
                'id_tahun_ajaran' => $idTahun,
            ]);
        });

        return back()->with('success', 'Pengurus organisasi ditetapkan.');
    }

    public function hapusOrganisasi(Organisasi $organisasi, int $pengurus)
    {
        $hapus = DB::table('pengurus_organisasi')
            ->where('id_organisasi', $organisasi->id_organisasi)->where('id_pengurus_org', $pengurus)->delete();
        abort_unless($hapus > 0, 404);

        return back()->with('success', 'Jabatan dilepas.');
    }

    private function cekKuota(int $idJabatan, int $terisi): void
    {
        $jabatan = DB::table('jabatan')->where('id_jabatan', $idJabatan)->first(['nama_jabatan', 'kuota']);

        if ($terisi >= $jabatan->kuota) {
            throw ValidationException::withMessages([
                'id_jabatan' => "Kuota jabatan {$jabatan->nama_jabatan} sudah penuh ({$jabatan->kuota} orang). Lepas pemegang lama terlebih dahulu.",
            ]);
        }
    }
}
