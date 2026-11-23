<?php

namespace App\Http\Controllers\Admin;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Fitur 2 — Kelola data siswa.
 *
 * Profil siswa selalu menempel pada akun ber-role siswa (siswa.id_user wajib & unik),
 * jadi akun dibuat lebih dulu lewat Kelola Akun. Siswa tidak dihapus; perubahan
 * keadaan dicatat lewat kolom status (aktif/lulus/pindah/keluar) agar riwayat
 * presensi, kas, dan pemilihan tetap utuh.
 */
class SiswaController extends AdminController
{
    public const STATUS = ['aktif', 'lulus', 'pindah', 'keluar'];

    public function index(Request $request)
    {
        $filter = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(self::STATUS)],
            'kelas' => ['nullable', 'integer'],
        ]);

        $tahun = TahunAjaran::aktif();
        $idTahun = $tahun?->id_tahun_ajaran;

        // Kelas pada tahun ajaran aktif saja; siswa bisa punya riwayat kelas di tahun lain.
        $siswa = DB::table('siswa as s')
            ->join('users as u', 'u.id_user', '=', 's.id_user')
            ->leftJoin('anggota_kelas as ak', function ($j) use ($idTahun) {
                $j->on('ak.id_siswa', '=', 's.id_siswa')
                    ->whereIn('ak.id_kelas', DB::table('kelas')->select('id_kelas')->where('id_tahun_ajaran', $idTahun ?? 0));
            })
            ->leftJoin('kelas as k', 'k.id_kelas', '=', 'ak.id_kelas')
            ->select('s.*', 'u.username', 'u.is_active', 'k.nama_kelas', 'ak.no_absen')
            ->when($filter['q'] ?? null, function ($q, $cari) {
                $q->where(fn ($w) => $w->where('s.nama_siswa', 'like', "%{$cari}%")
                    ->orWhere('s.nis', 'like', "%{$cari}%")
                    ->orWhere('s.nisn', 'like', "%{$cari}%"));
            })
            ->when($filter['status'] ?? null, fn ($q, $st) => $q->where('s.status', $st))
            ->when($filter['kelas'] ?? null, fn ($q, $k) => $q->where('k.id_kelas', $k))
            ->orderBy('s.nama_siswa')
            ->paginate(25)
            ->withQueryString();

        $kelas = DB::table('kelas')->where('id_tahun_ajaran', $idTahun ?? 0)->orderBy('nama_kelas')->get();

        return view('admin.siswa.index', compact('siswa', 'kelas', 'tahun') + ['statusList' => self::STATUS]);
    }

    public function create(Request $request)
    {
        return view('admin.siswa.form', [
            'siswa' => new Siswa(['id_user' => $request->integer('id_user') ?: null, 'status' => 'aktif']),
            'akun' => $this->akunTanpaProfilSiswa(),
            'statusList' => self::STATUS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['id_user'] = $request->validate(['id_user' => $this->aturanIdUserSiswa()])['id_user'];
        $data['foto_url'] = $this->simpanFoto($request);

        Siswa::create($data);

        return redirect()->route('admin.siswa.index')->with('success', "Siswa {$data['nama_siswa']} ditambahkan.");
    }

    public function edit(Siswa $siswa)
    {
        $username = DB::table('users')->where('id_user', $siswa->id_user)->value('username');

        return view('admin.siswa.form', ['siswa' => $siswa, 'username' => $username, 'statusList' => self::STATUS]);
    }

    public function update(Request $request, Siswa $siswa)
    {
        $data = $this->validasi($request, $siswa);

        if ($fotoBaru = $this->simpanFoto($request)) {
            $this->hapusFoto($siswa->foto_url);
            $data['foto_url'] = $fotoBaru;
        }

        $siswa->update($data);

        return redirect()->route('admin.siswa.index')->with('success', "Data {$siswa->nama_siswa} diperbarui.");
    }

    /** Ubah status siswa; opsional sekaligus menonaktifkan akunnya (lulus/pindah/keluar). */
    public function ubahStatus(Request $request, Siswa $siswa)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUS)],
            'nonaktifkan_akun' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($siswa, $data, $request) {
            $statusLama = $siswa->status;
            $siswa->update(['status' => $data['status']]);

            // Kembali aktif (mis. salah input "keluar") → akun ikut dipulihkan.
            if ($data['status'] === 'aktif' && $statusLama !== 'aktif') {
                DB::table('users')->where('id_user', $siswa->id_user)->update(['is_active' => true]);
            } elseif ($request->boolean('nonaktifkan_akun')) {
                DB::table('users')->where('id_user', $siswa->id_user)->update(['is_active' => false]);
            }
        });

        return back()->with('success', "Status {$siswa->nama_siswa} menjadi {$data['status']}.");
    }

    private function validasi(Request $request, ?Siswa $siswa = null): array
    {
        $abaikan = $siswa?->id_siswa;

        $data = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z.\-]+$/', Rule::unique('siswa', 'nis')->ignore($abaikan, 'id_siswa')],
            'nisn' => ['required', 'digits:10', Rule::unique('siswa', 'nisn')->ignore($abaikan, 'id_siswa')],
            'nama_siswa' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:50'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'no_hp' => ['nullable', 'string', 'max:15', 'regex:/^\+?[0-9]{8,14}$/'],
            'status' => ['required', Rule::in(self::STATUS)],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nisn.digits' => 'NISN harus 10 digit angka.',
            'no_hp.regex' => 'Nomor HP hanya angka (boleh diawali +), 8–14 digit.',
        ]);

        unset($data['foto']);

        return $data;
    }

    private function simpanFoto(Request $request): ?string
    {
        return $request->hasFile('foto') ? $request->file('foto')->store('siswa', 'public') : null;
    }

    /**
     * Akun tanpa profil siswa yang boleh dihubungkan ke profil baru.
     * Termasuk 'bendahara_kas': peran itu tetap memakai baris tabel `siswa`,
     * hanya berbeda pada hak kelola kasnya.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function akunTanpaProfilSiswa()
    {
        return DB::table('users as u')
            ->whereIn('u.role', ['siswa', 'bendahara_kas'])
            ->whereNotExists(fn ($q) => $q->from('siswa as s')->whereColumn('s.id_user', 'u.id_user'))
            ->orderBy('u.username')
            ->get(['u.id_user', 'u.username', 'u.is_active', 'u.role']);
    }

    /** Aturan id_user untuk profil siswa baru: akun ada, peran siswa/bendahara_kas, belum dipakai profil lain. */
    private function aturanIdUserSiswa(): array
    {
        return [
            'required', 'integer',
            Rule::exists('users', 'id_user')->whereIn('role', ['siswa', 'bendahara_kas']),
            Rule::unique('siswa', 'id_user'),
        ];
    }

    private function hapusFoto(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
