<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Tabel `tahun_ajaran` tidak punya timestamps.
class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';
    protected $primaryKey = 'id_tahun_ajaran';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
