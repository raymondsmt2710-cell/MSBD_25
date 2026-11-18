<?php

namespace App\Services;

use App\Models\IuranKas;
use App\Models\Kelas;
use App\Models\TransaksiKas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KasService
{
    /** @return array<string, mixed> */
    public function filter(Request $request): array
    {
        return [
            'dari' => $request->input('dari'),
            'sampai' => $request->input('sampai'),
            'jenis' => $request->input('jenis'),
            'kategori' => $request->input('kategori'),
            'q' => $request->input('q'),
            'termasuk_batal' => $request->boolean('termasuk_batal'),
            'page' => max(1, (int) $request->input('page', 1)),
        ];
    }

    /** @param array<string, mixed> $filter */
    public function query(Kelas $kelas, array $filter): Builder
    {
        return TransaksiKas::query()
            ->where('id_kelas', $kelas->id_kelas)
            ->tampil((bool) ($filter['termasuk_batal'] ?? false))
            ->when($filter['dari'] ?? null, fn (Builder $q, $value) => $q->whereDate('tanggal', '>=', $value))
            ->when($filter['sampai'] ?? null, fn (Builder $q, $value) => $q->whereDate('tanggal', '<=', $value))
            ->when($filter['jenis'] ?? null, fn (Builder $q, $value) => $q->where('jenis', $value))
            ->when($filter['kategori'] ?? null, fn (Builder $q, $value) => $q->where('kategori', $value))
            ->when($filter['q'] ?? null, function (Builder $q, $value) {
                $q->where(function (Builder $sub) use ($value) {
                    $sub->where('keterangan', 'like', "%{$value}%")
                        ->orWhere('kategori', 'like', "%{$value}%")
                        ->orWhereHas('siswa', fn (Builder $s) => $s->where('nama_siswa', 'like', "%{$value}%"));
                });
            });
    }

    /** @return array<string, float> */
    public function ringkasan(Kelas $kelas): array
    {
        $base = TransaksiKas::query()
            ->where('id_kelas', $kelas->id_kelas)
            ->aktif();

        $totalPemasukan = (float) (clone $base)->where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = (float) (clone $base)->where('jenis', 'pengeluaran')->sum('nominal');

        $awalBulan = now()->startOfMonth()->toDateString();
        $akhirBulan = now()->endOfMonth()->toDateString();

        $pemasukanBulanIni = (float) (clone $base)
            ->where('jenis', 'pemasukan')
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->sum('nominal');

        $pengeluaranBulanIni = (float) (clone $base)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->sum('nominal');

        return [
            'saldo' => $totalPemasukan - $totalPengeluaran,
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'pemasukan_bulan_ini' => $pemasukanBulanIni,
            'pengeluaran_bulan_ini' => $pengeluaranBulanIni,
        ];
    }

    /**
     * @param  Collection<int, int>|null  $idSiswaTerlihat
     * @return array<string, mixed>
     */
    public function matriks(Kelas $kelas, int $tahun, ?Collection $idSiswaTerlihat = null): array
    {
        $anggota = $kelas->anggotaKelas()
            ->with('siswa:id_siswa,nama_siswa,nis')
            ->when($idSiswaTerlihat !== null, fn ($q) => $q->whereIn('id_siswa', $idSiswaTerlihat))
            ->get()
            ->sortBy(fn ($row) => $row->no_absen ?? 999)
            ->values();

        $iuran = IuranKas::query()
            ->where('id_kelas', $kelas->id_kelas)
            ->where('periode', 'like', $tahun.'-%')
            ->get()
            ->groupBy('id_siswa');

        $namaBulan = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
            '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
            '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
        ];

        return [
            'tahun' => $tahun,
            'bulan' => $namaBulan,
            'nominal_default' => (float) (IuranKas::query()
                ->where('id_kelas', $kelas->id_kelas)
                ->where('periode', 'like', $tahun.'-%')
                ->latest('id_iuran')
                ->value('nominal') ?? 0),
            'siswa' => $anggota->map(function ($row) use ($iuran, $namaBulan) {
                $perSiswa = ($iuran[$row->id_siswa] ?? collect())
                    ->keyBy(fn ($item) => substr((string) $item->periode, 5, 2));

                return [
                    'id_siswa' => $row->id_siswa,
                    'nama' => $row->siswa?->nama_siswa,
                    'nis' => $row->siswa?->nis,
                    'no_absen' => $row->no_absen,
                    'iuran' => collect(array_keys($namaBulan))->mapWithKeys(function ($bulan) use ($perSiswa) {
                        $item = $perSiswa->get($bulan);

                        return [$bulan => $item ? [
                            'id_iuran' => $item->id_iuran,
                            'status' => $item->status->value,
                            'nominal' => (float) $item->nominal,
                            'catatan' => $item->catatan,
                        ] : null];
                    })->all(),
                ];
            })->values()->all(),
        ];
    }
}
