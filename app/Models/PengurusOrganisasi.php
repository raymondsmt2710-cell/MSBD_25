<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengurusOrganisasi extends Model
{
    protected $table = 'pengurus_organisasi';

    protected $primaryKey = 'id_pengurus_org';

    public $timestamps = false;

    protected $guarded = [];
}
