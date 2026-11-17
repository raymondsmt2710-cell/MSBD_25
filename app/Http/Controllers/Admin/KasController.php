<?php

namespace App\Http\Controllers\Admin;

use App\Models\TahunAjaran;
use App\Models\TransaksiKas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Fitur 19 — Kelola kas semua kelas.
 *
 * Pencatatan harian tetap oleh bendahara. Admin melihat seluruh kelas dan boleh
 * membatalkan transaksi sebagai koreksi. Transaksi TIDAK pernah dihapus atau diubah
 * nominalnya: pembatalan hanya menandai is_batal beserta siapa, kapan, dan alasannya,
 * sehingga jejak audit tetap utuh dan saldo dihitung tanpa transaksi batal.
 */
class KasController extends AdminController
{
    public function index(Request $request)
    {
        $filter = $request->validate([
            'kelas' => ['nullable', 'integer'],
            'jenis' => ['nullable', Rule::in(['pemasukan', 'pengeluaran'])],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'batal' => ['nullable', Rule::in(['semua', 'hanya', 'tanpa'])],
        ]);

        $idTahun = $this->idTahunAjaranTerpilih($request->query('tahun'));
        $modeBatal = $filter['batal'] ?? 'semua';

        // Saldo per kelas: hanya transaksi yang tidak dibatalkan.
        $saldo = DB::table('kelas as k')
            ->leftJoin('transaksi_kas as t', fn ($j) => $j->on('t.id_kelas', '=', 'k.id_kelas')->where('t.is_batal', false))
            ->where('k.id_tahun_ajaran', $idTahun ?? 0)
            ->groupBy('k.id_kelas', 'k.nama_kelas', 'k.tingkat')
            ->orderBy('k.tingkat')->orderBy('k.nama_kelas')
            ->select('k.id_kelas', 'k.nama_kelas')
            ->selectRaw("COALESCE(SUM(CASE WHEN t.jenis = 'pemasukan' THEN t.nominal END), 0) AS pemasukan,
                COALESCE(SUM(CASE WHEN t.jenis = 'pengeluaran' THEN t.nominal END), 0) AS pengeluaran,
                COUNT(t.id_transaksi) AS jumlah")
            ->get();

        $transaksi = DB::table('transaksi_kas as t')
            ->join('kelas as k', 'k.id_kelas', '=', 't.id_kelas')
            ->join('users as u', 'u.id_user', '=', 't.dicatat_oleh')
            ->leftJoin('users as ub', 'ub.id_user', '=', 't.dibatalkan_oleh')
            ->leftJoin('siswa as s', 's.id_siswa', '=', 't.id_siswa')
            ->where('k.id_tahun_ajaran', $idTahun ?? 0)
            ->when($filter['kelas'] ?? null, fn ($q, $k) => $q->where('t.id_kelas', $k))
            ->when($filter['jenis'] ?? null, fn ($q, $j) => $q->where('t.jenis', $j))
            ->when($filter['dari'] ?? null, fn ($q, $d) => $q->where('t.tanggal', '>=', $d))
            ->when($filter['sampai'] ?? null, fn ($q, $d) => $q->where('t.tanggal', '<=', $d))
            ->when($modeBatal === 'hanya', fn ($q) => $q->where('t.is_batal', true))
            ->when($modeBatal === 'tanpa', fn ($q) => $q->where('t.is_batal', false))
            ->orderByDesc('t.tanggal')
            ->orderByDesc('t.id_transaksi')
            ->select('t.*', 'k.nama_kelas', 'u.username as pencatat', 'ub.username as pembatal', 's.nama_siswa')
            ->paginate(30)
            ->withQueryString();

        return view('admin.kas.index', [
            'saldo' => $saldo,
            'transaksi' => $transaksi,
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'idTahun' => $idTahun,
            'modeBatal' => $modeBatal,
        ]);
    }

    public function batalkan(Request $request, TransaksiKas $transaksi)
    {
        $data = $request->validate([
            'alasan_batal' => ['required', 'string', 'min:5', 'max:255'],
        ], ['alasan_batal.required' => 'Alasan pembatalan wajib diisi untuk keperluan audit.']);

        // Update bersyarat: dua admin yang membatalkan bersamaan tidak saling menimpa jejak audit.
        $berubah = TransaksiKas::whereKey($transaksi->id_transaksi)
            ->where('is_batal', false)
            ->update([
                'is_batal' => true,
                'dibatalkan_oleh' => $request->user()->id_user,
                'dibatalkan_pada' => now(),
                'alasan_batal' => $data['alasan_batal'],
            ]);

        return $berubah
            ? back()->with('success', 'Transaksi dibatalkan dan tidak lagi dihitung dalam saldo kelas.')
            : back()->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
    }
}
