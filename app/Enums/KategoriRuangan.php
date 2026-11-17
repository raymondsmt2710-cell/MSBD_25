<?php

namespace App\Enums;

enum KategoriRuangan: string
{
    case Kelas = 'kelas';
    case Laboratorium = 'laboratorium';
    case Perpustakaan = 'perpustakaan';
    case RuangGuru = 'ruang_guru';
    case Kantin = 'kantin';
    case Uks = 'uks';
    case Toilet = 'toilet';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Kelas => 'Ruang Kelas',
            self::Laboratorium => 'Laboratorium',
            self::Perpustakaan => 'Perpustakaan',
            self::RuangGuru => 'Ruang Guru',
            self::Kantin => 'Kantin',
            self::Uks => 'UKS',
            self::Toilet => 'Toilet',
            self::Lainnya => 'Lainnya',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
