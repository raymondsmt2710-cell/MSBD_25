<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\TokenPresensi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresensiSiswaController extends Controller
{
    private const PESAN = [
        'BERHASIL'                        => 'Presensi berhasil dicatat. Selamat belajar!',
        'TOKEN_TIDAK_VALID'               => 'QR tidak dikenali.',
        'TOKEN_KEDALUWARSA_ATAU_TERPAKAI' => 'QR sudah kedaluwarsa atau sudah dipakai. Pindai QR yang baru di layar guru.',
        'SESI_DITUTUP'                    => 'Sesi presensi sudah ditutup.',
        'BUKAN_ANGGOTA_KELAS'             => 'Anda bukan anggota kelas ini.',
        'SUDAH_PRESENSI'                  => 'Anda sudah tercatat pada sesi ini.',
    ];

    private function idSiswa(): int
    {
        return Auth::user()->siswa?->id_siswa ?? abort(403, 'Akun ini belum terhubung ke data siswa.');
    }

    /** GET tidak mengubah data: hanya menampilkan tombol konfirmasi. */
    public function konfirmasi(string $kode)
    {
        $valid = TokenPresensi::masihValid($kode); // Stored Function

        return view('siswa.presensi.konfirmasi', compact('kode', 'valid'));
    }

    /** Memanggil sp_scan_presensi di dalam transaksi (materi Transaction: COMMIT / ROLLBACK). */
    public function scan(string $kode)
    {
        $idSiswa = $this->idSiswa();

        try {
            DB::beginTransaction();
            DB::statement('CALL sp_scan_presensi(?, ?, @status)', [$kode, $idSiswa]);
            $status = DB::selectOne('SELECT @status AS status')->status;
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            // 1062 = duplikat (siswa menekan tombol dua kali bersamaan)
            $status = ($e->errorInfo[1] ?? null) === 1062 ? 'SUDAH_PRESENSI' : null;

            if ($status === null) {
                Log::error($e);

                return redirect()->route('siswa.presensi.riwayat')->with('error', 'Terjadi kesalahan saat mencatat presensi.');
            }
        }

        $pesan = self::PESAN[$status] ?? 'Status tidak dikenal.';

        return redirect()->route('siswa.presensi.riwayat')->with($status === 'BERHASIL' ? 'success' : 'error', $pesan);
    }

    public function riwayat()
    {
        $idSiswa = $this->idSiswa();

        $riwayat = DB::table('v_rekap_presensi_siswa') // View
            ->where('id_siswa', $idSiswa)
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai')
            ->select('tanggal', 'nama_mapel', 'jam_mulai', 'jam_selesai', 'status', 'metode', 'waktu_presensi')
            ->paginate(15);

        $ringkasan = [
            'persen' => Presensi::persentaseKehadiran($idSiswa),
            'hadir'  => Presensi::jumlahStatus($idSiswa, 'hadir'),
            'izin'   => Presensi::jumlahStatus($idSiswa, 'izin'),
            'sakit'  => Presensi::jumlahStatus($idSiswa, 'sakit'),
            'alpa'   => Presensi::jumlahStatus($idSiswa, 'alpa'),
        ];

        return view('siswa.presensi.riwayat', compact('riwayat', 'ringkasan'));
    }
}
