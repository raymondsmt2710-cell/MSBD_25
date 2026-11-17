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
            self::Admin => 'admin.dashboard',
            self::Guru => 'guru.beranda',
            self::Siswa => 'siswa.beranda',
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