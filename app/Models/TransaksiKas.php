<?php

namespace App\Models;

use App\Enums\JenisKas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('transaksi_kas', key: 'id_transaksi')]
#[Fillable([
    'id_kelas', 'jenis', 'kategori', 'nominal', 'tanggal', 'keterangan',
    'id_siswa', 'bukti_url', 'dicatat_oleh', 'is_batal', 'alasan_batal', 'dibatalkan_oleh', 'dibatalkan_pada',
])]
class TransaksiKas extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
            'is_batal' => 'boolean',
            'dibatalkan_pada' => 'datetime',
            'jenis' => JenisKas::class,
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh', 'id_user');
    }

    public function pembatal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh', 'id_user');
    }

    public function iuran(): HasOne
    {
        return $this->hasOne(IuranKas::class, 'id_transaksi', 'id_transaksi');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_batal', false);
    }

    public function scopeTampil(Builder $query, bool $termasukBatal = false): Builder
    {
        return $termasukBatal ? $query : $query->where('is_batal', false);
    }
}
