<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('jabatan', key: 'id_jabatan', timestamps: false)]
#[Fillable(['nama_jabatan', 'lingkup', 'kuota', 'urutan'])]
class Jabatan extends Model
{
    public function pengurusKelas(): HasMany
    {
        return $this->hasMany(PengurusKelas::class, 'id_jabatan', 'id_jabatan');
    }
}
