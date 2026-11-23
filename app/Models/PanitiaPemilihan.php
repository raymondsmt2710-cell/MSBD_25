<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanitiaPemilihan extends Model
{
    protected $table = 'panitia_pemilihan';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function pemilihan()
    {
        return $this->belongsTo(Pemilihan::class, 'id_pemilihan', 'id_pemilihan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
