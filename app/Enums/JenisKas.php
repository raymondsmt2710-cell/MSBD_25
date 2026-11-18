<?php

namespace App\Enums;

enum JenisKas: string
{
    case Pemasukan = 'pemasukan';
    case Pengeluaran = 'pengeluaran';

    public function label(): string
    {
        return match ($this) {
            self::Pemasukan => 'Pemasukan',
            self::Pengeluaran => 'Pengeluaran',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Pemasukan => 'emerald',
            self::Pengeluaran => 'rose',
        };
    }
}
