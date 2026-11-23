<?php

namespace App\Enums;

enum KategoriKas: string
{
    case IuranRutin = 'Iuran Rutin';
    case Denda = 'Denda';
    case Donasi = 'Donasi';
    case Konsumsi = 'Konsumsi';
    case Perlengkapan = 'Perlengkapan';
    case Kegiatan = 'Kegiatan';
    case Lainnya = 'Lainnya';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
