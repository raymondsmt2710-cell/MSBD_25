<?php

namespace App\Http\Controllers\Denah;

use App\Enums\KategoriRuangan;
use App\Enums\StatusRuangan;
use App\Http\Controllers\Controller;
use App\Services\DenahService;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DenahController extends Controller
{
    public function __construct(
        private readonly Akses $akses,
        private readonly DenahService $denah,
    ) {}

    public function index(Request $request): View
    {
        $kelas = $this->akses->kelasTerpilih(
            $request->user(),
            $request->integer('id_kelas') ?: null,
        );

        abort_if(! $kelas, 403, 'Anda belum terhubung ke kelas aktif mana pun.');
        Gate::authorize('kas.lihat', $kelas);

        $bisaKelolaKursi = Gate::allows('denah.kelola-kursi', $kelas);
        $denah = $this->denah->denahAktif($kelas, $bisaKelolaKursi, $request->user()->id_user);
        $ruangan = $this->denah->ruangan();

        // Orang tua hanya boleh melihat kursi anaknya; null = seluruh kelas.
        $idSiswaTerlihat = $this->akses->siswaTerlihat($request->user(), $kelas);

        return view('denah.index', [
            'initial' => [
                'idKelas' => $kelas->id_kelas,
                'daftarKelas' => $this->akses->daftarKelas($request->user())
                    ->map(fn ($item) => [
                        'id_kelas' => $item->id_kelas,
                        'nama_kelas' => $item->nama_kelas,
                    ])
                    ->values()
                    ->all(),
                'bisaKelolaKursi' => $bisaKelolaKursi,
                'bisaKelolaRuangan' => Gate::allows('denah.kelola-ruangan'),
                'denah' => $denah,
                'kursi' => $denah ? $this->denah->kursi($denah, $kelas, $idSiswaTerlihat) : [],
                'siswa' => $this->denah->siswa($kelas, $idSiswaTerlihat),
                'ruangan' => $ruangan,
                'gedung' => collect($ruangan)->pluck('gedung')->unique()->values()->all(),
                'lantai' => collect($ruangan)->pluck('lantai')->unique()->sort()->values()->all(),
                'kategoriRuangan' => collect(KategoriRuangan::cases())->map(fn ($item) => [
                    'value' => $item->value,
                    'label' => $item->label(),
                ])->values()->all(),
                'statusRuangan' => collect(StatusRuangan::cases())->map(fn ($item) => [
                    'value' => $item->value,
                    'label' => $item->label(),
                ])->values()->all(),
                'formRuanganKosong' => [
                    'id_ruangan' => null,
                    'kode_ruangan' => '',
                    'nama_ruangan' => '',
                    'kategori' => 'kelas',
                    'gedung' => 'Gedung Utama',
                    'lantai' => 1,
                    'kapasitas' => 30,
                    'fasilitas' => '',
                    'deskripsi' => '',
                    'posisi_x' => 1,
                    'posisi_y' => 1,
                    'lebar_grid' => 4,
                    'tinggi_grid' => 3,
                    'status' => 'tersedia',
                    'jam_buka' => '07:00',
                    'jam_tutup' => '15:00',
                ],
            ],
        ]);
    }
}
