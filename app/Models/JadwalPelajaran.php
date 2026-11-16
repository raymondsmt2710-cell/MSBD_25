<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    protected $table = 'jadwal_pelajaran';
    protected $primaryKey = 'id_jadwal';
    public $timestamps = false;
    protected $guarded = [];
}
