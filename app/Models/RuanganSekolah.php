<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('ruangan_sekolah', key: 'id_ruangan')]
#[Fillable([
    'kode_ruangan', 'nama_ruangan', 'kategori', 'gedung', 'lantai', 'kapasitas',
    'fasilitas', 'deskripsi', 'posisi_x', 'posisi_y', 'lebar_grid', 'tinggi_grid',
    'status', 'jam_buka', 'jam_tutup', 'gambar_url',
])]
class RuanganSekolah extends Model
{
    protected function casts(): array
    {
        return [
            'fasilitas' => 'array',
            'lantai' => 'integer',
            'kapasitas' => 'integer',
            'posisi_x' => 'integer',
            'posisi_y' => 'integer',
            'lebar_grid' => 'integer',
            'tinggi_grid' => 'integer',
        ];
    }
}
