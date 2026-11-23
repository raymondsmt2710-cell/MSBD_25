<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\TokenPresensi;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresensiSiswaController extends Controller
{
    private const PESAN = [
        'BERHASIL'                        => 'Presensi berhasil dicatat. Selamat belajar!',
        'TOKEN_TIDAK_VALID'               => 'QR tidak dikenali.',
        'TOKEN_BELUM_BERLAKU'             => 'Jam pelajaran belum dimulai. Pindai lagi setelah pelajaran dimulai.',
        'TOKEN_KEDALUWARSA_ATAU_TERPAKAI' => 'QR sudah kedaluwarsa. Pindai QR yang ditampilkan guru.',
        'SESI_DITUTUP'                    => 'Sesi presensi sudah ditutup.',
        'BUKAN_ANGGOTA_KELAS'             => 'Anda bukan anggota kelas ini.',
        'SUDAH_PRESENSI'                  => 'Anda sudah tercatat pada sesi ini.',
    ];

    private function idSiswa(): int
    {
        return Auth::user()->siswa?->id_siswa ?? abort(403, 'Akun ini belum terhubung ke data siswa.');
    }


    public function menu()
    {
        $idSiswa = $this->idSiswa();

        $ringkasan = [
            'persen' => Presensi::persentaseKehadiran($idSiswa),
            'hadir'  => Presensi::jumlahStatus($idSiswa, 'hadir'),
            'izin'   => Presensi::jumlahStatus($idSiswa, 'izin'),
            'sakit'  => Presensi::jumlahStatus($idSiswa, 'sakit'),
            'alpa'   => Presensi::jumlahStatus($idSiswa, 'alpa'),
        ];

        return view('siswa.presensi.menu', compact('ringkasan'));
    }


    public function pindai()
    {
        return view('siswa.presensi.pindai');
    }


    public function konfirmasi(string $kode)
    {
        $valid = TokenPresensi::masihValid($kode);

        return view('siswa.presensi.konfirmasi', compact('kode', 'valid'));
    }


    public function scan(Request $request, string $kode)
    {
        $idSiswa = $this->idSiswa();

        $lokasi = $request->validate([
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'akurasi_meter'  => 'nullable|numeric|min:0|max:100000',
        ]);

        $lat = isset($lokasi['latitude']) ? round((float) $lokasi['latitude'], 7) : null;
        $lng = isset($lokasi['longitude']) ? round((float) $lokasi['longitude'], 7) : null;
        $akurasi = isset($lokasi['akurasi_meter']) ? round((float) $lokasi['akurasi_meter'], 2) : null;

        try {
            DB::beginTransaction();
            DB::statement('CALL sp_scan_presensi(?, ?, ?, ?, ?, @status)', [$kode, $idSiswa, $lat, $lng, $akurasi]);
            $status = DB::selectOne('SELECT @status AS status')->status;
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();

            $status = ($e->errorInfo[1] ?? null) === 1062 ? 'SUDAH_PRESENSI' : null;

            if ($status === null) {
                Log::error($e);

                return redirect()->route('siswa.presensi.riwayat')->with('error', 'Terjadi kesalahan saat mencatat presensi.');
            }
        }

        $pesan = self::PESAN[$status] ?? 'Status tidak dikenal.';

        return redirect()->route('siswa.presensi.riwayat')->with($status === 'BERHASIL' ? 'success' : 'error', $pesan);
    }


    public function riwayat(Request $request)
    {
        $idSiswa = $this->idSiswa();

        $pekan = $this->awalPekan($request->query('pekan'));

        $jadwal = DB::table('v_roster_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->orderByRaw("FIELD(hari, 'senin','selasa','rabu','kamis','jumat','sabtu')")
            ->orderBy('jam_mulai')
            ->get();


        $sesi = DB::table('v_sesi_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->whereBetween('tanggal', [$pekan->toDateString(), $pekan->copy()->addDays(5)->toDateString()])
            ->get()
            ->keyBy(fn ($s) => $s->id_jadwal.'|'.$s->tanggal);

        $hariKe = ['senin' => 0, 'selasa' => 1, 'rabu' => 2, 'kamis' => 3, 'jumat' => 4, 'sabtu' => 5];

        $roster = $jadwal->map(function ($j) use ($pekan, $sesi, $hariKe) {
            $tanggal = $pekan->copy()->addDays($hariKe[$j->hari] ?? 0);
            $kunci = $j->id_jadwal.'|'.$tanggal->toDateString();
            $s = $sesi->get($kunci);

            $status = '-';
            if ($s) {
                $status = $s->status_presensi
                    ?? ($s->status_sesi === 'dibuka' ? 'belum' : '-');
            }

            return (object) [
                'hari'          => $j->hari,
                'tanggal'       => $tanggal,
                'jam_mulai'     => $j->jam_mulai,
                'jam_selesai'   => $j->jam_selesai,
                'nama_mapel'    => $j->nama_mapel,
                'nama_guru'     => $j->nama_guru,
                'nama_kelas'    => $j->nama_kelas,
                'status'        => $status,
                'metode'        => $s->metode ?? null,
                'waktu_presensi' => $s->waktu_presensi ?? null,
            ];
        });

        $ringkasan = [
            'persen' => Presensi::persentaseKehadiran($idSiswa),
            'hadir'  => Presensi::jumlahStatus($idSiswa, 'hadir'),
            'izin'   => Presensi::jumlahStatus($idSiswa, 'izin'),
            'sakit'  => Presensi::jumlahStatus($idSiswa, 'sakit'),
            'alpa'   => Presensi::jumlahStatus($idSiswa, 'alpa'),
        ];

        $pekanSebelum = $pekan->copy()->subWeek()->toDateString();
        $pekanBerikut = $pekan->copy()->addWeek()->toDateString();

        return view('siswa.presensi.riwayat', compact(
            'roster', 'ringkasan', 'pekan', 'pekanSebelum', 'pekanBerikut'
        ));
    }


    private function awalPekan(?string $tanggal): Carbon
    {
        try {
            $dasar = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        } catch (\Throwable $e) {
            $dasar = Carbon::today();
        }

        return $dasar->startOfWeek(Carbon::MONDAY)->startOfDay();
    }
}
