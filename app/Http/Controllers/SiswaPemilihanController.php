<?php

namespace App\Http\Controllers;

use App\Models\Pemilihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiswaPemilihanController extends Controller
{
    private const PESAN = [
        'BERHASIL'             => 'Suara Anda berhasil direkam. Terima kasih sudah memilih!',
        'PEMILIHAN_TIDAK_VALID' => 'Pemilihan tidak ditemukan.',
        'BELUM_DIMULAI'        => 'Pemilihan belum dimulai.',
        'SUDAH_DITUTUP'        => 'Pemilihan sudah ditutup.',
        'BUKAN_PEMILIH'        => 'Anda tidak terdaftar sebagai pemilih pada pemilihan ini.',
        'SUDAH_MEMILIH'        => 'Anda sudah memberikan suara. Suara tidak dapat diubah.',
        'KANDIDAT_TIDAK_VALID' => 'Kandidat tidak valid untuk pemilihan ini.',
    ];

    private function idSiswa(): int
    {
        return Auth::user()->siswa?->id_siswa
            ?? abort(403, 'Akun ini belum terhubung ke data siswa.');
    }


    public function index()
    {
        $idSiswa = $this->idSiswa();

        $pemilihan = DB::table('pemilihan as p')
            ->join('daftar_pemilih as dp', function ($j) use ($idSiswa) {
                $j->on('dp.id_pemilihan', '=', 'p.id_pemilihan')
                  ->where('dp.id_siswa', '=', $idSiswa);
            })
            ->join('organisasi as o', 'o.id_organisasi', '=', 'p.id_organisasi')
            ->orderByRaw("FIELD(p.status, 'berlangsung', 'draf', 'selesai')")
            ->orderByDesc('p.waktu_mulai')
            ->select(
                'p.id_pemilihan', 'p.judul', 'p.deskripsi', 'p.status',
                'p.waktu_mulai', 'p.waktu_selesai',
                'o.nama_organisasi', 'dp.sudah_memilih'
            )
            ->get();

        $partisipasi = DB::table('v_partisipasi_pemilihan')
            ->whereIn('id_pemilihan', $pemilihan->pluck('id_pemilihan'))
            ->get()
            ->keyBy('id_pemilihan');

        return view('siswa.pemilihan.index', compact('pemilihan', 'partisipasi'));
    }


    public function pilih(int $id)
    {
        $idSiswa = $this->idSiswa();

        $pemilihan = Pemilihan::findOrFail($id);

        if (! Pemilihan::bolehMemilih($id, $idSiswa)) {
            return redirect()->route('siswa.pemilihan.index')
                ->with('error', Pemilihan::sudahMemilih($id, $idSiswa)
                    ? 'Anda sudah memberikan suara pada pemilihan ini.'
                    : 'Anda belum dapat memilih pada pemilihan ini.');
        }

        $kandidat = DB::table('v_kandidat_pemilihan')
            ->where('id_pemilihan', $id)
            ->orderBy('nomor_urut')
            ->get();

        return view('siswa.pemilihan.pilih', compact('pemilihan', 'kandidat'));
    }


    public function simpan(Request $request, int $id)
    {
        $idSiswa = $this->idSiswa();

        $data = $request->validate([
            'id_kandidat' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();
            DB::statement('CALL sp_rekam_suara(?,?,?,@status)', [$id, $idSiswa, $data['id_kandidat']]);
            $status = DB::selectOne('SELECT @status AS status')->status;
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return redirect()->route('siswa.pemilihan.index')
                ->with('error', 'Terjadi kesalahan saat merekam suara. Silakan coba lagi.');
        }

        $pesan = self::PESAN[$status] ?? 'Status tidak dikenal.';

        return redirect()->route('siswa.pemilihan.index')
            ->with($status === 'BERHASIL' ? 'success' : 'error', $pesan);
    }


    public function hasil(int $id)
    {
        $this->idSiswa();

        $pemilihan = Pemilihan::findOrFail($id);

        if ($pemilihan->status !== 'selesai') {
            return redirect()->route('siswa.pemilihan.index')
                ->with('error', 'Hasil pemilihan baru dapat dilihat setelah pemilihan ditutup.');
        }

        $hasil = DB::table('v_hasil_pemilihan')
            ->where('id_pemilihan', $id)
            ->orderByDesc('jumlah_suara')
            ->orderBy('nomor_urut')
            ->get();

        $partisipasi = DB::table('v_partisipasi_pemilihan')
            ->where('id_pemilihan', $id)
            ->first();

        $tertinggi = $hasil->max('jumlah_suara');

        return view('siswa.pemilihan.hasil', compact('pemilihan', 'hasil', 'partisipasi', 'tertinggi'));
    }
}
