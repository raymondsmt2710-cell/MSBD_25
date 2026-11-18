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

/**
 * Halaman akun untuk pengguna yang sudah login: ganti sandi sendiri dan keluar.
 *
 * Kenapa lewat stored procedure, bukan $user->update():
 * user MySQL per peran (siswa_app, guru_app, ortu_app) hanya punya SELECT pada
 * tabel `users`. UPDATE langsung akan gagal dengan error 1142. Pola proyek ini
 * untuk kasus seperti itu adalah menulis lewat procedure lalu memberi hak
 * EXECUTE — sama seperti sp_scan_presensi dan sp_rekam_suara.
 *
 * Verifikasi sandi lama tetap di sini (Hash::check), karena kolom password_hash
 * memakai collation case-insensitive sehingga perbandingan '=' di SQL tidak bisa
 * diandalkan untuk hash bcrypt.
 */
class AkunController extends Controller
{
    /** Pesan untuk tiap status yang bisa dikembalikan sp_ubah_sandi. */
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

    /**
     * Data diri sesuai peran, dalam bentuk label => nilai siap tampil.
     *
     * Dulu bagian ini ada di dashboard. Setelah dashboard dirampingkan, tempatnya
     * di sini. Hanya ditampilkan, tidak bisa diedit: menulis ke tabel siswa/guru
     * butuh GRANT UPDATE yang sengaja tidak diberikan ke user per peran.
     *
     * Peran tanpa baris siswa/guru (admin, orang tua) mendapat array kosong,
     * sehingga bagian ini tidak dirender sama sekali.
     */
    private function dataDiri(User $pengguna): array
    {
        if ($pengguna->role === Role::Siswa) {
            $siswa = $pengguna->siswa;

            if ($siswa === null) {
                return [];
            }

            // Nama kelas dibaca dari view roster yang sama dengan dashboard siswa;
            // hak SELECT-nya sudah ada untuk siswa_app.
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

    /** "Tempat, 12 Desember 2008"; "—" bila keduanya kosong. */
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
            // Dikembalikan sebagai error validasi supaya tampil di field yang benar.
            throw ValidationException::withMessages([
                'sandi_lama' => 'Sandi lama tidak cocok.',
            ]);
        }

        $hashBaru = Hash::make($data['sandi']);

        // MySQL (produksi): tulis lewat procedure agar hak tabel users tetap tertutup.
        // SQLite (test): procedure tidak ada, jadi pakai jalur Eloquent biasa.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            try {
                // Tiga parameter IN, satu parameter OUT (@status). @status diinisialisasi
                // dulu supaya nilai sisa dari request sebelumnya di koneksi yang sama
                // (koneksi persisten) tidak terbaca sebagai hasil.
                DB::statement('SET @status = NULL');
                DB::statement('CALL sp_ubah_sandi(?, ?, ?, @status)', [
                    $pengguna->id_user,
                    $pengguna->password_hash,
                    $hashBaru,
                ]);

                $status = DB::selectOne('SELECT @status AS status')->status ?? null;
            } catch (QueryException $e) {
                // 1305 = procedure belum ada (migrasi 2026_11_18_000004 belum jalan).
                // 1370 = hak EXECUTE belum diberikan. Keduanya masalah deployment,
                // jadi dicatat ke log, tetapi pengguna cukup melihat pesan biasa
                // ketimbang halaman 500.
                Log::error('Gagal memanggil sp_ubah_sandi.', [
                    'id_user' => $pengguna->id_user,
                    'kode' => $e->errorInfo[1] ?? null,
                    'pesan' => $e->getMessage(),
                ]);

                return back()->with('error', 'Sandi gagal diubah. Silakan hubungi administrator.');
            }

            if ($status !== 'BERHASIL') {
                // Jangan pernah bilang "berhasil" bila procedure menolak menulis.
                Log::warning('Ganti sandi ditolak sp_ubah_sandi.', [
                    'id_user' => $pengguna->id_user,
                    'status' => $status,
                ]);

                return back()->with('error', self::PESAN[$status] ?? 'Sandi gagal diubah. Silakan coba lagi.');
            }
        } else {
            $pengguna->update(['password_hash' => $hashBaru]);
        }

        // Sesi tidak diputus: pengguna tetap login setelah mengganti sandi sendiri.
        return back()->with('success', self::PESAN['BERHASIL']);
    }
}
