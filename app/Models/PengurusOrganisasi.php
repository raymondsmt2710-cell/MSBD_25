<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Table('pengurus_organisasi', key: 'id_pengurus_org', timestamps: false)]
#[Fillable(['id_organisasi', 'id_tahun_ajaran', 'id_siswa', 'id_jabatan', 'bidang'])]
class PengurusOrganisasi extends Model
{
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }


    public static function jumlahUntukOrganisasi(int $idOrganisasi, int $idTahunAjaran): int
    {
        $hasil = DB::selectOne(
            'SELECT fn_jumlah_pengurus_organisasi(?, ?) AS hasil',
            [$idOrganisasi, $idTahunAjaran]
        );

        return $hasil ? (int) $hasil->hasil : 0;
    }
}
