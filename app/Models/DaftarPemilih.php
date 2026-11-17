<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Primary key gabungan (id_pemilihan, id_siswa), tanpa timestamps.
class DaftarPemilih extends Model
{
    protected $table = 'daftar_pemilih';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sudah_memilih' => 'boolean',
            'waktu_memilih' => 'datetime',
        ];
    }

    public function pemilihan()
    {
        return $this->belongsTo(Pemilihan::class, 'id_pemilihan', 'id_pemilihan');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }
}
