<?php

namespace App\Enums;

enum StatusRuangan: string
{
    case Tersedia = 'tersedia';
    case Maintenance = 'maintenance';
    case Tutup = 'tutup';

    public function label(): string
    {
        return match ($this) {
            self::Tersedia => 'Tersedia',
            self::Maintenance => 'Maintenance',
            self::Tutup => 'Tutup',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Tersedia => 'emerald',
            self::Maintenance => 'amber',
            self::Tutup => 'gray',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
