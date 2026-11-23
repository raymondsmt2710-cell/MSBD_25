<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Table('pengurus_kelas', key: 'id_pengurus_kelas', timestamps: false)]
#[Fillable(['id_kelas', 'id_siswa', 'id_jabatan'])]
class PengurusKelas extends Model
{
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }


    public static function jumlahUntukKelas(int $idKelas): int
    {
        $hasil = DB::selectOne('SELECT fn_jumlah_pengurus_kelas(?) AS hasil', [$idKelas]);

        return $hasil ? (int) $hasil->hasil : 0;
    }
}
