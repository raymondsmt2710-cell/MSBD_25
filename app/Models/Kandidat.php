<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Tabel `kandidat` tidak punya created_at/updated_at.
class Kandidat extends Model
{
    protected $table = 'kandidat';
    protected $primaryKey = 'id_kandidat';
    public $timestamps = false;
    protected $guarded = [];

    public function pemilihan()
    {
        return $this->belongsTo(Pemilihan::class, 'id_pemilihan', 'id_pemilihan');
    }

    public function ketua()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa_ketua', 'id_siswa');
    }

    public function wakil()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa_wakil', 'id_siswa');
    }

    /** Nama pasangan, mis. "Budi & Ani" atau "Budi" bila tanpa wakil. */
    public function getNamaPasanganAttribute(): string
    {
        return $this->wakil
            ? $this->ketua->nama_siswa.' & '.$this->wakil->nama_siswa
            : ($this->ketua->nama_siswa ?? '-');
    }
}
