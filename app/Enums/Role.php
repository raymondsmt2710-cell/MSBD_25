<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Guru = 'guru';
    case Siswa = 'siswa';
    case OrangTua = 'orang_tua';

    /**
     * Siswa dengan hak tambahan untuk mengelola kas kelasnya sendiri,
     * setara bendahara. Profilnya tetap baris di tabel `siswa`, sehingga
     * semua fitur siswa lain (presensi, perangkat, pemilihan) tetap berlaku.
     */
    case BendaharaKas = 'bendahara_kas';

    public function home(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Guru => 'guru.beranda',
            self::Siswa, self::BendaharaKas => 'siswa.beranda',
            self::OrangTua => 'ortu.index',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Guru => 'Guru',
            self::Siswa => 'Siswa',
            self::BendaharaKas => 'Siswa Bendahara Kas',
            self::OrangTua => 'Orang Tua',
        };
    }

    /** Apakah peran ini memakai profil siswa (baris tabel `siswa`). */
    public function punyaProfilSiswa(): bool
    {
        return $this === self::Siswa || $this === self::BendaharaKas;
    }

    /** Apakah peran ini akun siswa/siswa-bendahara: bisa membaca fitur sisi siswa. */
    public function siswaSeperti(): bool
    {
        return $this->punyaProfilSiswa();
    }
}
