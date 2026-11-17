<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Suara anonim: tidak ada kolom id_siswa, dan tidak ada timestamps.
// Model ini hanya untuk membaca/menghitung. Penulisan WAJIB lewat sp_rekam_suara.
class Suara extends Model
{
    protected $table = 'suara';
    protected $primaryKey = 'id_suara';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function kandidat()
    {
        return $this->belongsTo(Kandidat::class, 'id_kandidat', 'id_kandidat');
    }
}
