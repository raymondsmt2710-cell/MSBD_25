<?php

namespace App\Http\Controllers\Kas;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Services\KasService;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportKasController extends Controller
{
    public function __construct(
        private readonly Akses $akses,
        private readonly KasService $kas,
    ) {}

    public function csv(Request $request): StreamedResponse
    {
        $kelas = $this->kelas($request);
        Gate::authorize('kas.lihat', $kelas);

        $filter = $this->kas->filter($request);
        $ringkasan = $this->kas->ringkasan($kelas);
        $query = $this->kas->query($kelas, $filter)
            ->with(['siswa:id_siswa,nama_siswa', 'pencatat:id_user,username'])
            ->orderBy('tanggal')
            ->orderBy('id_transaksi');

        $namaFile = 'kas-'.str($kelas->nama_kelas)->slug().'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($query, $ringkasan) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Tanggal', 'Jenis', 'Kategori', 'Nominal', 'Keterangan',
                'Siswa', 'Dicatat Oleh', 'Status',
            ]);

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        optional($row->tanggal)->format('Y-m-d'),
                        $row->jenis?->label() ?? $row->jenis,
                        $row->kategori,
                        number_format((float) $row->nominal, 2, ',', ''),
                        $row->keterangan,
                        $row->siswa?->nama_siswa ?? '-',
                        $row->pencatat?->username ?? '-',
                        $row->is_batal ? 'Dibatalkan' : 'Aktif',
                    ]);
                }
            });

            fputcsv($out, []);
            fputcsv($out, ['TOTAL PEMASUKAN', '', '', number_format($ringkasan['total_pemasukan'], 2, ',', '')]);
            fputcsv($out, ['TOTAL PENGELUARAN', '', '', number_format($ringkasan['total_pengeluaran'], 2, ',', '')]);
            fputcsv($out, ['SALDO AKHIR', '', '', number_format($ringkasan['saldo'], 2, ',', '')]);
            fclose($out);
        }, $namaFile, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function cetak(Request $request)
    {
        $kelas = $this->kelas($request);
        Gate::authorize('kas.lihat', $kelas);

        $filter = $this->kas->filter($request);

        $transaksi = $this->kas->query($kelas, $filter)
            ->with(['siswa:id_siswa,nama_siswa', 'pencatat:id_user,username'])
            ->orderBy('tanggal')
            ->orderBy('id_transaksi')
            ->get();

        return view('kas.cetak', [
            'kelas' => $kelas,
            'transaksi' => $transaksi,
            'ringkasan' => $this->kas->ringkasan($kelas),
            'filter' => $filter,
        ]);
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
}
