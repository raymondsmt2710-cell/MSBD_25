<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AkunController extends Controller
{

    private const PESAN = [
        'AKUN_TIDAK_DITEMUKAN' => 'Akun tidak ditemukan. Silakan login ulang.',
        'SANDI_LAMA_SALAH' => 'Sandi lama tidak cocok. Sandi Anda tidak diubah.',
        'SANDI_BARU_KOSONG' => 'Sandi baru wajib diisi.',
        'BERHASIL' => 'Sandi berhasil diubah. Gunakan sandi baru pada login berikutnya.',
    ];

    public function profil(Request $request): View
    {
        $pengguna = $request->user();

        return view('akun.profil', [
            'pengguna' => $pengguna,
            'dataDiri' => $this->dataDiri($pengguna),
        ]);
    }


    private function dataDiri(User $pengguna): array
    {
        if ($pengguna->role->punyaProfilSiswa()) {
            $siswa = $pengguna->siswa;

            if ($siswa === null) {
                return [];
            }



            $kelas = DB::table('v_roster_presensi_siswa')
                ->where('id_siswa', $siswa->id_siswa)
                ->value('nama_kelas');

            return [
                'Nama' => $siswa->nama_siswa,
                'NIS' => $siswa->nis,
                'NISN' => $siswa->nisn,
                'Kelas' => $kelas,
                'Jenis Kelamin' => $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                'Tempat, Tanggal Lahir' => $this->tempatTanggalLahir($siswa),
                'No. HP' => $siswa->no_hp,
                'Alamat' => $siswa->alamat,
                'Status' => $siswa->status,
            ];
        }

        if ($pengguna->role === Role::Guru) {
            $guru = $pengguna->guru;

            if ($guru === null) {
                return [];
            }

            $diampu = DB::table('jadwal_pelajaran as jp')
                ->join('kelas as k', 'k.id_kelas', '=', 'jp.id_kelas')
                ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'jp.id_mapel')
                ->where('jp.id_guru', $guru->id_guru)
                ->select('m.nama_mapel', 'k.nama_kelas')
                ->distinct()
                ->get();

            return [
                'Nama' => $guru->nama_guru,
                'NIP' => $guru->nip,
                'Jenis Kelamin' => $guru->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                'Email' => $guru->email,
                'No. HP' => $guru->no_hp,
                'Mata Pelajaran' => $diampu->pluck('nama_mapel')->unique()->implode(', '),
                'Kelas Diampu' => $diampu->pluck('nama_kelas')->unique()->implode(', '),
            ];
        }

        return [];
    }


    private function tempatTanggalLahir(object $siswa): ?string
    {
        $tempat = $siswa->tempat_lahir;
        $tanggal = $siswa->tanggal_lahir === null
            ? null
            : Carbon::parse($siswa->tanggal_lahir)->locale('id')->isoFormat('D MMMM YYYY');

        if ($tempat === null && $tanggal === null) {
            return null;
        }

        return trim(($tempat ?? '').($tempat !== null && $tanggal !== null ? ', ' : '').($tanggal ?? ''));
    }

    public function ubahSandi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sandi_lama' => ['required', 'string'],
            'sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'sandi_lama.required' => 'Sandi lama wajib diisi.',
            'sandi.required' => 'Sandi baru wajib diisi.',
            'sandi.min' => 'Sandi baru minimal 8 karakter.',
            'sandi.confirmed' => 'Konfirmasi sandi baru tidak cocok.',
        ]);

        $pengguna = $request->user();

        if (! Hash::check($data['sandi_lama'], $pengguna->password_hash)) {

            throw ValidationException::withMessages([
                'sandi_lama' => 'Sandi lama tidak cocok.',
            ]);
        }

        $hashBaru = Hash::make($data['sandi']);



        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            try {



                DB::statement('SET @status = NULL');
                DB::statement('CALL sp_ubah_sandi(?, ?, ?, @status)', [
                    $pengguna->id_user,
                    $pengguna->password_hash,
                    $hashBaru,
                ]);

                $status = DB::selectOne('SELECT @status AS status')->status ?? null;
            } catch (QueryException $e) {




                Log::error('Gagal memanggil sp_ubah_sandi.', [
                    'id_user' => $pengguna->id_user,
                    'kode' => $e->errorInfo[1] ?? null,
                    'pesan' => $e->getMessage(),
                ]);

                return back()->with('error', 'Sandi gagal diubah. Silakan hubungi administrator.');
            }

            if ($status !== 'BERHASIL') {

                Log::warning('Ganti sandi ditolak sp_ubah_sandi.', [
                    'id_user' => $pengguna->id_user,
                    'status' => $status,
                ]);

                return back()->with('error', self::PESAN[$status] ?? 'Sandi gagal diubah. Silakan coba lagi.');
            }
        } else {
            $pengguna->update(['password_hash' => $hashBaru]);
        }


        return back()->with('success', self::PESAN['BERHASIL']);
    }
}
