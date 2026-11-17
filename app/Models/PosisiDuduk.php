<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('posisi_duduk', key: 'id_posisi', timestamps: false)]
#[Fillable(['id_denah', 'id_siswa', 'baris', 'kolom'])]
class PosisiDuduk extends Model
{
    public function denah(): BelongsTo
    {
        return $this->belongsTo(DenahKelas::class, 'id_denah', 'id_denah');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }
}
