<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosisiDuduk extends Model
{
    protected $table = 'posisi_duduk';

    protected $primaryKey = 'id_posisi';

    public $timestamps = false;

    protected $guarded = [];
}
