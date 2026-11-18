<?php

namespace App\Models;

use App\Enums\StatusIuran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('iuran_kas', key: 'id_iuran')]
#[Fillable([
    'id_kelas', 'id_siswa', 'periode', 'nominal', 'status', 'bukti_url',
    'id_transaksi', 'diverifikasi_oleh', 'diverifikasi_pada', 'catatan',
])]
class IuranKas extends Model
{
    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'status' => StatusIuran::class,
            'diverifikasi_pada' => 'datetime',
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

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'id_transaksi', 'id_transaksi');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh', 'id_user');
    }
}
