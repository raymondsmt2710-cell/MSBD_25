<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

/**
 * Halaman awal untuk tamu (belum login).
 *
 * Statistik diambil langsung dari database, tetapi dibungkus try/catch:
 * halaman publik tidak boleh error hanya karena database belum siap
 * (mis. sebelum migration dijalankan di komputer lain).
 */
class BerandaController extends Controller
{
    public function index()
    {
        // Kalau sudah login, langsung ke halaman sesuai peran.
        // Cabang orang_tua wajib ada: tanpa itu akun wali murid dilempar ke halaman
        // kelola pemilihan yang menolaknya dengan 403.
        if (auth()->check()) {
            return redirect()->to(match (auth()->user()->role) {
                'guru' => route('guru.beranda'),
                'siswa' => route('siswa.pemilihan.index'),
                'orang_tua' => route('ortu.index'),
                default => route('kelola.pemilihan.index'),
            });
        }

        $statistik = $this->statistik();

        $fitur = [
            [
                'ikon' => 'qr-code-scan',
                'judul' => 'Presensi QR Sekali Pakai',
                'deskripsi' => 'Guru membuka sesi, siswa memindai QR yang hanya berlaku sekali. Kehadiran tercatat otomatis.',
            ],
            [
                'ikon' => 'calendar-week',
                'judul' => 'Roster Kelas Digital',
                'deskripsi' => 'Jadwal pelajaran per kelas dan per guru, tersusun rapi dan mudah dilihat.',
            ],
            [
                'ikon' => 'check2-square',
                'judul' => 'Pemilihan Suara Elektronik',
                'deskripsi' => 'Pemilihan pengurus organisasi dengan suara rahasia, satu siswa satu suara, dan hasil yang dapat diaudit.',
            ],
            [
                'ikon' => 'cash-coin',
                'judul' => 'Pencatatan Kas Kelas',
                'deskripsi' => 'Catat pemasukan dan pengeluaran kas kelas, lengkap dengan saldo terkini.',
            ],
            [
                'ikon' => 'diagram-3',
                'judul' => 'Perangkat Organisasi & Kelas',
                'deskripsi' => 'Susunan pengurus organisasi dan pengurus kelas per tahun ajaran.',
            ],
            [
                'ikon' => 'grid-3x3',
                'judul' => 'Denah Kelas',
                'deskripsi' => 'Atur posisi duduk siswa dalam kelas dan lihat denahnya secara visual.',
            ],
            [
                'ikon' => 'person-hearts',
                'judul' => 'Pemantauan Orang Tua',
                'deskripsi' => 'Orang tua dapat memantau kehadiran dan aktivitas anak di sekolah.',
            ],
        ];

        return view('beranda', [
            'jumlahSiswa' => $statistik['siswa'],
            'jumlahGuru' => $statistik['guru'],
            'jumlahKelas' => $statistik['kelas'],
            'jumlahPemilihan' => $statistik['pemilihan'],
            'fitur' => $fitur,
        ]);
    }

    /** Statistik ringkas; 0 bila tabel belum ada atau database belum siap. */
    private function statistik(): array
    {
        $hitung = function (string $tabel): int {
            try {
                return (int) DB::table($tabel)->count();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        return [
            'siswa' => $hitung('siswa'),
            'guru' => $hitung('guru'),
            'kelas' => $hitung('kelas'),
            'pemilihan' => $hitung('pemilihan'),
        ];
    }
}
