<?php

namespace App\Http\Controllers\Kas;

use App\Enums\KategoriKas;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Services\KasService;
use App\Support\Akses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
