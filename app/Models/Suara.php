<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
