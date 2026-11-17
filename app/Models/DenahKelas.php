<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DenahKelas extends Model
{
    protected $table = 'denah_kelas';

    protected $primaryKey = 'id_denah';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['berlaku_mulai' => 'date', 'is_aktif' => 'boolean'];
    }
}
