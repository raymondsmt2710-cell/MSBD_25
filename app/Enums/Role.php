<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Guru = 'guru';
    case Siswa = 'siswa';
    case OrangTua = 'orang_tua';


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


    public function punyaProfilSiswa(): bool
    {
        return $this === self::Siswa || $this === self::BendaharaKas;
    }


    public function siswaSeperti(): bool
    {
        return $this->punyaProfilSiswa();
    }
}
