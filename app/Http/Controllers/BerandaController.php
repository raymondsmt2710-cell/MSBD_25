<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BerandaController extends Controller
{
    public function index()
    {




        if (auth()->check()) {
            $peran = auth()->user()->role?->value ?? (string) auth()->user()->role;

            return redirect()->to(match ($peran) {
                'guru' => route('guru.beranda'),
                'siswa' => route('siswa.pemilihan.index'),
                'orang_tua' => route('ortu.index'),
                'admin' => route('admin.dashboard'),
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
