<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('organisasi', key: 'id_organisasi', timestamps: false)]
#[Fillable(['nama_organisasi', 'singkatan', 'deskripsi', 'id_pembina', 'logo_url'])]
class Organisasi extends Model
{
    public function pembina(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_pembina', 'id_guru');
    }

    public function pengurus(): HasMany
    {
        return $this->hasMany(PengurusOrganisasi::class, 'id_organisasi', 'id_organisasi');
    }
}
