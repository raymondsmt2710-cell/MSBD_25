<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Tabel `organisasi` tidak punya timestamps.
class Organisasi extends Model
{
    protected $table = 'organisasi';
    protected $primaryKey = 'id_organisasi';
    public $timestamps = false;
    protected $guarded = [];

    public function pembina()
    {
        return $this->belongsTo(Guru::class, 'id_pembina', 'id_guru');
    }

    public function pengurus()
    {
        return $this->hasMany(PengurusOrganisasi::class, 'id_organisasi', 'id_organisasi');
    }
}
