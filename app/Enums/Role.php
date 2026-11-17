<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Guru = 'guru';
    case Siswa = 'siswa';
    case OrangTua = 'orang_tua';

    public function home(): string
    {
        return match ($this) {
            self::Admin => 'kelola.pemilihan.index',
            self::Guru => 'guru.presensi.index',
            self::Siswa => 'siswa.pemilihan.index',
            self::OrangTua => 'ortu.index',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Guru => 'Guru',
            self::Siswa => 'Siswa',
            self::OrangTua => 'Orang Tua',
        };
    }
}