<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesiPresensi extends Model
{
    protected $table = 'sesi_presensi';
    protected $primaryKey = 'id_sesi';
    public $timestamps = false;
    protected $guarded = [];

    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'id_jadwal', 'id_jadwal');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'id_sesi', 'id_sesi');
    }
}
