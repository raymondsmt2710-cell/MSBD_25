<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnggotaKelas extends Model
{
    protected $table = 'anggota_kelas';

    protected $primaryKey = 'id_anggota';

    public $timestamps = false;

    protected $guarded = [];
}
