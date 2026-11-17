<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengurusKelas extends Model
{
    protected $table = 'pengurus_kelas';

    protected $primaryKey = 'id_pengurus_kelas';

    public $timestamps = false;

    protected $guarded = [];
}
