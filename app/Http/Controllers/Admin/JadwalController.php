<?php

namespace App\Http\Controllers\Admin;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JadwalController extends AdminController
{
    public const HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

    public function index(Request $request)
    {
        return $this->halaman($request);
    }

    public function edit(Request $request, JadwalPelajaran $jadwal)
    {
        $request->query->set('kelas', (string) $jadwal->id_kelas);
        $request->query->set('semester', $jadwal->semester);

        return $this->halaman($request, $jadwal);
    }

    private function halaman(Request $request, ?JadwalPelajaran $edit = null)
    {
        $filter = $request->validate([
            'kelas' => ['nullable', 'integer'],
            'semester' => ['nullable', Rule::in(['ganjil', 'genap'])],
        ]);

        $kelas = isset($filter['kelas']) ? Kelas::find($filter['kelas']) : null;
        $idTahun = $kelas?->id_tahun_ajaran ?? $this->idTahunAjaranTerpilih($request->query('tahun'));
        $semester = $filter['semester'] ?? $this->semesterSekarang();

        $daftarKelas = DB::table('kelas as k')
            ->where('k.id_tahun_ajaran', $idTahun ?? 0)
            ->select('k.id_kelas', 'k.nama_kelas')
            ->selectSub(DB::table('jadwal_pelajaran')->selectRaw('COUNT(*)')->whereColumn('id_kelas', 'k.id_kelas')->where('semester', $semester), 'jumlah_jadwal')
            ->orderBy('k.tingkat')->orderBy('k.nama_kelas')
            ->get();

        $jadwal = collect();
        if ($kelas) {
            $jadwal = DB::table('jadwal_pelajaran as j')
                ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'j.id_mapel')
                ->join('guru as g', 'g.id_guru', '=', 'j.id_guru')
                ->where('j.id_kelas', $kelas->id_kelas)
                ->where('j.semester', $semester)
                ->orderByRaw("FIELD(j.hari, 'senin','selasa','rabu','kamis','jumat','sabtu')")
                ->orderBy('j.jam_mulai')
                ->select('j.*', 'm.kode_mapel', 'm.nama_mapel', 'g.nama_guru')
                ->selectSub(DB::table('sesi_presensi')->selectRaw('COUNT(*)')->whereColumn('id_jadwal', 'j.id_jadwal'), 'jumlah_sesi')
                ->get()
                ->groupBy('hari');
        }

        return view('admin.jadwal.index', [
            'kelas' => $kelas,
            'daftarKelas' => $daftarKelas,
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'idTahun' => $idTahun,
            'semester' => $semester,
            'jadwal' => $jadwal,
            'edit' => $edit ?? new JadwalPelajaran(['jam_mulai' => '07:30', 'jam_selesai' => '09:00']),
            'mapel' => DB::table('mata_pelajaran')->orderBy('nama_mapel')->get(),
            'guru' => DB::table('guru as g')->join('users as u', 'u.id_user', '=', 'g.id_user')
                ->where('u.is_active', true)->orderBy('g.nama_guru')->get(['g.id_guru', 'g.nama_guru']),
            'hariList' => self::HARI,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        JadwalPelajaran::create($data);

        return redirect()->route('admin.jadwal.index', ['kelas' => $data['id_kelas'], 'semester' => $data['semester']])
            ->with('success', 'Jadwal ditambahkan.');
    }

    public function update(Request $request, JadwalPelajaran $jadwal)
    {
        $data = $this->validasi($request, $jadwal);


        if ((int) $data['id_kelas'] !== (int) $jadwal->id_kelas && $this->punyaSesi($jadwal)) {
            return back()->withInput()->with('error', 'Kelas tidak dapat dipindah karena jadwal ini sudah punya sesi presensi.');
        }

        $jadwal->update($data);

        return redirect()->route('admin.jadwal.index', ['kelas' => $jadwal->id_kelas, 'semester' => $jadwal->semester])
            ->with('success', 'Jadwal diperbarui.');
    }

    public function destroy(JadwalPelajaran $jadwal)
    {


        if ($this->punyaSesi($jadwal)) {
            return back()->with('error', 'Jadwal tidak dapat dihapus karena sudah dipakai membuka sesi presensi. Riwayat presensi akan ikut terhapus.');
        }

        $jadwal->delete();

        return back()->with('success', 'Jadwal dihapus.');
    }

    private function punyaSesi(JadwalPelajaran $jadwal): bool
    {
        return DB::table('sesi_presensi')->where('id_jadwal', $jadwal->id_jadwal)->exists();
    }


    private function semesterSekarang(): string
    {
        return now()->month >= 7 ? 'ganjil' : 'genap';
    }

    private function validasi(Request $request, ?JadwalPelajaran $jadwal = null): array
    {
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_mapel' => ['required', 'integer', 'exists:mata_pelajaran,id_mapel'],
            'id_guru' => ['required', 'integer', 'exists:guru,id_guru'],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
            'hari' => ['required', Rule::in(self::HARI)],
            'jam_mulai' => ['required', 'date_format:H:i,H:i:s'],
            'jam_selesai' => ['required', 'date_format:H:i,H:i:s', 'after:jam_mulai'],
        ], ['jam_selesai.after' => 'Jam selesai harus setelah jam mulai.']);

        $data['jam_mulai'] = substr($data['jam_mulai'], 0, 5).':00';
        $data['jam_selesai'] = substr($data['jam_selesai'], 0, 5).':00';

        $this->cekBentrok($data, $jadwal?->id_jadwal);

        return $data;
    }

    private function cekBentrok(array $d, ?int $abaikan): void
    {
        $idTahun = DB::table('kelas')->where('id_kelas', $d['id_kelas'])->value('id_tahun_ajaran');

        $beririsan = fn () => DB::table('jadwal_pelajaran as j')
            ->join('kelas as k', 'k.id_kelas', '=', 'j.id_kelas')
            ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'j.id_mapel')
            ->where('j.semester', $d['semester'])
            ->where('j.hari', $d['hari'])
            ->where('j.jam_mulai', '<', $d['jam_selesai'])
            ->where('j.jam_selesai', '>', $d['jam_mulai'])
            ->when($abaikan, fn ($q) => $q->where('j.id_jadwal', '!=', $abaikan));

        $kelas = $beririsan()->where('j.id_kelas', $d['id_kelas'])->first(['m.nama_mapel', 'j.jam_mulai', 'j.jam_selesai']);
        if ($kelas) {
            throw ValidationException::withMessages(['jam_mulai' => sprintf(
                'Bentrok dengan %s di kelas ini (%s–%s).', $kelas->nama_mapel, substr($kelas->jam_mulai, 0, 5), substr($kelas->jam_selesai, 0, 5)
            )]);
        }

        $guru = $beririsan()->where('j.id_guru', $d['id_guru'])->where('k.id_tahun_ajaran', $idTahun)
            ->first(['k.nama_kelas', 'm.nama_mapel', 'j.jam_mulai', 'j.jam_selesai']);
        if ($guru) {
            throw ValidationException::withMessages(['id_guru' => sprintf(
                'Guru ini sudah mengajar %s di %s pada %s–%s.', $guru->nama_mapel, $guru->nama_kelas, substr($guru->jam_mulai, 0, 5), substr($guru->jam_selesai, 0, 5)
            )]);
        }
    }
}
