<?php

namespace App\Enums;

enum StatusIuran: string
{
    case BelumBayar = 'belum_bayar';
    case Menunggu = 'menunggu';
    case Terverifikasi = 'terverifikasi';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::BelumBayar => 'Belum bayar',
            self::Menunggu => 'Menunggu',
            self::Terverifikasi => 'Lunas',
            self::Ditolak => 'Ditolak',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::BelumBayar => 'gray',
            self::Menunggu => 'amber',
            self::Terverifikasi => 'emerald',
            self::Ditolak => 'rose',
        };
    }
}
