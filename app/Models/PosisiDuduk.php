<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Satu baris = satu murid pada satu sisi meja.
// Meja (baris, kolom) punya dua sisi: 'kiri' dan 'kanan'.
#[Table('posisi_duduk', key: 'id_posisi', timestamps: false)]
#[Fillable(['id_denah', 'id_siswa', 'baris', 'kolom', 'sisi'])]
class PosisiDuduk extends Model
{
    /** Sisi meja yang tersedia, urut sesuai tampilan. */
    public const SISI = ['kiri', 'kanan'];

    public function denah(): BelongsTo
    {
        return $this->belongsTo(DenahKelas::class, 'id_denah', 'id_denah');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }
}
