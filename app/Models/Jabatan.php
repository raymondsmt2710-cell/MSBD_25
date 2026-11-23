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

    public function pengurusOrganisasi(): HasMany
    {
        return $this->hasMany(PengurusOrganisasi::class, 'id_jabatan', 'id_jabatan');
    }


    public function scopeLingkup($query, string $lingkup)
    {
        return $query->where('lingkup', $lingkup);
    }


    public function scopeUrut($query)
    {
        return $query->orderBy('urutan')->orderBy('id_jabatan');
    }
}
