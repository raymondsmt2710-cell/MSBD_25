<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Jabatan;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Support\Collection;

final class Akses
{
    /**
     * Semua kelas yang boleh dilihat user pada tahun ajaran aktif.
     *
     * @return Collection<int, Kelas>
     */
    public function daftarKelas(User $user): Collection
    {
        if ($user->role === Role::Admin) {
            return Kelas::query()
                ->whereHas('tahunAjaran', fn ($q) => $q->where('is_aktif', true))
                ->orderBy('nama_kelas')
                ->get();
        }

        if ($user->role->siswaSeperti()) {
            $kelas = $this->kelasAktifSiswa($user);

            return $kelas ? collect([$kelas]) : collect();
        }

        if ($user->role === Role::Guru) {
            $guru = $user->guru;
            if (! $guru) {
                return collect();
            }

            return Kelas::query()
                ->whereHas('tahunAjaran', fn ($q) => $q->where('is_aktif', true))
                ->where(function ($query) use ($guru) {
                    $query->where('id_wali_kelas', $guru->id_guru)
                        ->orWhereHas('jadwalPelajaran', fn ($q) => $q->where('id_guru', $guru->id_guru));
                })
                ->orderBy('nama_kelas')
                ->get();
        }

        if ($user->role === Role::OrangTua) {
            $orangTua = $user->orangTua;
            if (! $orangTua) {
                return collect();
            }

            $idSiswa = $orangTua->siswa()->pluck('siswa.id_siswa');

            return Kelas::query()
                ->whereHas('tahunAjaran', fn ($q) => $q->where('is_aktif', true))
                ->whereHas('anggotaKelas', fn ($q) => $q->whereIn('id_siswa', $idSiswa))
                ->orderBy('nama_kelas')
                ->get();
        }

        return collect();
    }

    public function kelasAktifSiswa(User $user): ?Kelas
    {
        $siswa = $user->siswa;
        if (! $siswa) {
            return null;
        }

        return Kelas::query()
            ->whereHas('tahunAjaran', fn ($q) => $q->where('is_aktif', true))
            ->whereHas('anggotaKelas', fn ($q) => $q->where('id_siswa', $siswa->id_siswa))
            ->first();
    }

    public function kelasTerpilih(User $user, ?int $idKelas = null): ?Kelas
    {
        $daftar = $this->daftarKelas($user);

        if ($idKelas) {
            $dipilih = $daftar->firstWhere('id_kelas', $idKelas);
            if ($dipilih) {
                return $dipilih;
            }
        }

        return $daftar->first();
    }

    /**
     * ID siswa yang boleh dilihat pada matriks iuran.
     * Null berarti seluruh anggota kelas boleh dilihat.
     *
     * @return Collection<int, int>|null
     */
    public function siswaTerlihat(User $user, Kelas $kelas): ?Collection
    {
        // Peran bendahara_kas boleh mengelola kas kelasnya, jadi harus melihat
        // seluruh baris matriks iuran — sama seperti bendahara dan ketua kelas.
        if (
            $user->role === Role::BendaharaKas
            || $this->isBendahara($user, $kelas)
            || $this->isKetuaKelas($user, $kelas)
        ) {
            return null;
        }

        if ($user->role->siswaSeperti()) {
            $siswa = $user->siswa;

            return $siswa ? collect([$siswa->id_siswa]) : collect();
        }

        if ($user->role === Role::OrangTua) {
            $orangTua = $user->orangTua;
            if (! $orangTua) {
                return collect();
            }

            return $orangTua->siswa()
                ->whereHas('anggotaKelas', fn ($q) => $q->where('id_kelas', $kelas->id_kelas))
                ->pluck('siswa.id_siswa');
        }

        return null;
    }

    public function namaJabatan(User $user, Kelas $kelas): Collection
    {
        $siswa = $user->siswa;
        if (! $siswa) {
            return collect();
        }

        return Jabatan::query()
            ->whereHas('pengurusKelas', fn ($q) => $q
                ->where('id_kelas', $kelas->id_kelas)
                ->where('id_siswa', $siswa->id_siswa))
            ->pluck('nama_jabatan');
    }

    public function isBendahara(User $user, ?Kelas $kelas = null): bool
    {
        $kelas ??= $this->kelasAktifSiswa($user);
        if (! $kelas) {
            return false;
        }

        return $this->namaJabatan($user, $kelas)
            ->contains(fn ($nama) => str_contains(strtolower((string) $nama), 'bendahara'));
    }

    public function isKetuaKelas(User $user, ?Kelas $kelas = null): bool
    {
        $kelas ??= $this->kelasAktifSiswa($user);
        if (! $kelas) {
            return false;
        }

        return $this->namaJabatan($user, $kelas)
            ->contains(fn ($nama) => str_contains(strtolower((string) $nama), 'ketua'));
    }

    public function bisaLihatKelas(User $user, Kelas $kelas): bool
    {
        return $this->daftarKelas($user)->contains('id_kelas', $kelas->id_kelas);
    }

    public function bisaKelolaKas(User $user, Kelas $kelas): bool
    {
        // Peran bendahara_kas: ditandai langsung di akunnya, tidak bergantung
        // pada nama jabatan, tetapi tetap terbatas pada kelasnya sendiri
        // (kelas diverifikasi lewat bisaLihatKelas).
        if ($user->role === Role::BendaharaKas) {
            return $this->bisaLihatKelas($user, $kelas);
        }

        return $user->role === Role::Admin || $this->isBendahara($user, $kelas);
    }

    /**
     * Denah kelas hanya boleh diubah administrator. Semua peran lain —
     * termasuk ketua kelas dan bendahara kas — hanya dapat melihat.
     * $kelas tetap diterima agar tanda tangan sama dengan gate lain dan
     * pemanggil tidak perlu diubah; nilainya sengaja tidak dipakai karena
     * wewenang ini tidak lagi bergantung pada jabatan di kelas.
     */
    public function bisaKelolaKursi(User $user, Kelas $kelas): bool
    {
        return $user->role === Role::Admin;
    }

    public function bisaKelolaRuangan(User $user): bool
    {
        return $user->role === Role::Admin;
    }
}
