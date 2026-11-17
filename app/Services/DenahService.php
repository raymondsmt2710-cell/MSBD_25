<?php

namespace App\Services;

use App\Models\DenahKelas;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\PengurusKelas;
use App\Models\PosisiDuduk;
use App\Models\RuanganSekolah;

class DenahService
{
    public function denahAktif(Kelas $kelas, bool $buatJikaTidakAda = false, ?int $idUser = null): ?DenahKelas
    {
        $denah = DenahKelas::query()
            ->where('id_kelas', $kelas->id_kelas)
            ->where('is_aktif', true)
            ->latest('id_denah')
            ->first();

        if (! $denah && $buatJikaTidakAda && $idUser) {
            $denah = DenahKelas::create([
                'id_kelas' => $kelas->id_kelas,
                'jumlah_baris' => 6,
                'jumlah_kolom' => 4,
                'posisi_meja_guru' => 'depan_tengah',
                'posisi_pintu' => 'kanan',
                'berlaku_mulai' => now()->toDateString(),
                'dibuat_oleh' => $idUser,
                'is_aktif' => true,
            ]);
        }

        return $denah;
    }

    /**
     * Daftar meja beserta maksimal 2 murid yang duduk di sana.
     * Bentuk tiap entri:
     *   ['baris' => int, 'kolom' => int, 'murid' => ['kiri' => ?array, 'kanan' => ?array]]
     *
     * @return array<int, array<string, mixed>>
     */
    public function kursi(DenahKelas $denah, Kelas $kelas): array
    {
        $jabatan = PengurusKelas::query()
            ->where('id_kelas', $kelas->id_kelas)
            ->with('jabatan:id_jabatan,nama_jabatan')
            ->get()
            ->groupBy('id_siswa')
            ->map(fn ($rows) => $rows->pluck('jabatan.nama_jabatan')->filter()->implode(', '));

        return PosisiDuduk::query()
            ->where('id_denah', $denah->id_denah)
            ->with('siswa:id_siswa,nama_siswa,nis')
            ->orderBy('baris')
            ->orderBy('kolom')
            ->get()
            ->groupBy(fn (PosisiDuduk $posisi) => $posisi->baris.'-'.$posisi->kolom)
            ->map(function ($rows, string $kunci) use ($jabatan) {
                [$baris, $kolom] = array_map('intval', explode('-', $kunci));

                $murid = ['kiri' => null, 'kanan' => null];

                foreach ($rows as $posisi) {
                    $sisi = in_array($posisi->sisi, PosisiDuduk::SISI, true) ? $posisi->sisi : 'kiri';

                    $murid[$sisi] = [
                        'id_posisi' => $posisi->id_posisi,
                        'sisi' => $sisi,
                        'siswa' => $posisi->siswa ? [
                            'id_siswa' => $posisi->siswa->id_siswa,
                            'nama_siswa' => $posisi->siswa->nama_siswa,
                            'nis' => $posisi->siswa->nis,
                            'jabatan' => $jabatan[$posisi->id_siswa] ?? null,
                        ] : null,
                    ];
                }

                return [
                    'baris' => $baris,
                    'kolom' => $kolom,
                    'murid' => $murid,
                ];
            })
            ->sortBy([['baris', 'asc'], ['kolom', 'asc']])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function siswa(Kelas $kelas): array
    {
        return $kelas->anggotaKelas()
            ->with('siswa:id_siswa,nama_siswa,nis')
            ->get()
            ->sortBy(fn ($item) => $item->no_absen ?? 999)
            ->map(fn ($item) => [
                'id_siswa' => $item->id_siswa,
                'nama_siswa' => $item->siswa?->nama_siswa,
                'nis' => $item->siswa?->nis,
                'no_absen' => $item->no_absen,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function ruangan(): array
    {
        $hari = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'][now()->dayOfWeek] ?? 'senin';

        $jadwal = JadwalPelajaran::query()
            ->where('hari', $hari)
            ->whereTime('jam_mulai', '<=', now()->format('H:i:s'))
            ->whereTime('jam_selesai', '>=', now()->format('H:i:s'))
            ->with([
                'kelas:id_kelas,nama_kelas,ruangan',
                'mapel:id_mapel,nama_mapel',
                'guru:id_guru,nama_guru',
            ])
            ->get();

        return RuanganSekolah::query()
            ->orderBy('gedung')
            ->orderBy('lantai')
            ->orderBy('nama_ruangan')
            ->get()
            ->map(function (RuanganSekolah $ruangan) use ($jadwal) {
                $aktif = $jadwal->first(
                    fn (JadwalPelajaran $item) => $item->kelas?->ruangan === $ruangan->kode_ruangan,
                );

                return [
                    ...$ruangan->toArray(),
                    'jadwal_sekarang' => $aktif ? [
                        'nama_kelas' => $aktif->kelas?->nama_kelas,
                        'nama_mapel' => $aktif->mapel?->nama_mapel,
                        'nama_guru' => $aktif->guru?->nama_guru,
                        'jam_mulai' => substr((string) $aktif->jam_mulai, 0, 5),
                        'jam_selesai' => substr((string) $aktif->jam_selesai, 0, 5),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }
}
