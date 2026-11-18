<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('denah_kelas', key: 'id_denah', timestamps: false)]
#[Fillable([
    'id_kelas', 'jumlah_baris', 'jumlah_kolom', 'posisi_meja_guru',
    'posisi_pintu', 'berlaku_mulai', 'dibuat_oleh', 'is_aktif',
])]
class DenahKelas extends Model
{
    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id_user');
    }

    public function posisiDuduk(): HasMany
    {
        return $this->hasMany(PosisiDuduk::class, 'id_denah', 'id_denah');
    }
}
