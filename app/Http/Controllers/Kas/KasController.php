<?php

namespace App\Http\Controllers\Kas;

use App\Enums\KategoriKas;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\User;
use App\Services\KasService;
use App\Support\Akses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KasController extends Controller
{
    public function __construct(
        private readonly Akses $akses,
        private readonly KasService $kas,
    ) {}

    public function index(Request $request): View
    {
        $kelas = $this->kelas($request);
        Gate::authorize('kas.lihat', $kelas);

        return view('kas.index', [
            'initial' => $this->payload($request, $kelas),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $kelas = $this->kelas($request);
        Gate::authorize('kas.lihat', $kelas);

        return response()->json($this->payload($request, $kelas));
    }

    private function kelas(Request $request): Kelas
    {
        $kelas = $this->akses->kelasTerpilih(
            $request->user(),
            $request->integer('id_kelas') ?: null,
        );

        abort_if(! $kelas, 403, 'Anda belum terhubung ke kelas aktif mana pun.');

        return $kelas;
    }

    /**
     * ID siswa yang pembayarannya boleh dilaporkan akun ini.
     *
     * Hanya orang tua, dan hanya untuk anaknya sendiri. Peran lain mendapat
     * daftar kosong: bendahara memakai jalur verifikasi, bukan laporan.
     *
     * @param  Collection<int, int>|null  $idSiswaTerlihat
     * @return array<int, bool>
     */
    private function idSiswaBolehLapor(User $user, ?Collection $idSiswaTerlihat): array
    {
        if ($user->role !== Role::OrangTua || $idSiswaTerlihat === null) {
            return [];
        }

        return $idSiswaTerlihat->mapWithKeys(fn ($id) => [(int) $id => true])->all();
    }

    /** @return array<string, mixed> */
    private function payload(Request $request, Kelas $kelas): array
    {
        $user = $request->user();
        $filter = $this->kas->filter($request);
        $tahun = (int) $request->input('tahun', now()->year);

        $transaksi = $this->kas->query($kelas, $filter)
            ->with([
                'siswa:id_siswa,nama_siswa,nis',
                'pencatat:id_user,username',
                'pembatal:id_user,username',
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->paginate(15, ['*'], 'page', $filter['page'])
            ->withQueryString();

        $idSiswaTerlihat = $this->akses->siswaTerlihat($user, $kelas);

        return [
            'ringkasan' => $this->kas->ringkasan($kelas),
            'transaksi' => $transaksi,
            'matriks' => $this->kas->matriks($kelas, $tahun, $idSiswaTerlihat),
            'kelas' => [
                'id_kelas' => $kelas->id_kelas,
                'nama_kelas' => $kelas->nama_kelas,
                'ruangan' => $kelas->ruangan,
            ],
            'idKelas' => $kelas->id_kelas,
            'daftarKelas' => $this->akses->daftarKelas($user)
                ->map(fn (Kelas $item) => [
                    'id_kelas' => $item->id_kelas,
                    'nama_kelas' => $item->nama_kelas,
                ])
                ->values()
                ->all(),
            'anggota' => $kelas->anggotaKelas()
                ->with('siswa:id_siswa,nama_siswa,nis')
                // Orang tua hanya boleh menerima data anaknya sendiri, sama
                // seperti matriks iuran. Tanpa filter ini nama & NIS seluruh
                // teman sekelas ikut terkirim ke browser orang tua.
                ->when($idSiswaTerlihat !== null, fn ($q) => $q->whereIn('id_siswa', $idSiswaTerlihat))
                ->get()
                ->sortBy(fn ($item) => $item->no_absen ?? 999)
                ->map(fn ($item) => [
                    'id_siswa' => $item->id_siswa,
                    'nama' => $item->siswa?->nama_siswa,
                    'nis' => $item->siswa?->nis,
                    'no_absen' => $item->no_absen,
                ])
                ->values()
                ->all(),
            'bisaKelola' => Gate::allows('kas.kelola', $kelas),
            // ID siswa yang boleh dilaporkan pembayarannya oleh akun ini
            // (orang tua: hanya anaknya sendiri). Kosong bagi peran lain.
            'bisaLaporAnak' => $this->idSiswaBolehLapor($user, $idSiswaTerlihat),
            'kategori' => KategoriKas::values(),
            'filter' => $filter,
            'tahun' => $tahun,
            'formKosong' => [
                'id_transaksi' => null,
                'tanggal' => now()->toDateString(),
                'jenis' => 'pemasukan',
                'kategori' => KategoriKas::IuranRutin->value,
                'nominal' => '',
                'keterangan' => '',
                'id_siswa' => '',
            ],
        ];
    }
}
