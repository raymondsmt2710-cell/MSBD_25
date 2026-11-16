<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Presensi extends Model
{
    protected $table = 'presensi';
    protected $primaryKey = 'id_presensi';
    public $timestamps = false;
    protected $guarded = [];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    /** Stored Function fn_persentase_kehadiran */
    public static function persentaseKehadiran(int $idSiswa): float
    {
        $r = DB::selectOne('SELECT fn_persentase_kehadiran(?) AS persen', [$idSiswa]);

        return $r ? (float) $r->persen : 0.0;
    }

    /** Stored Function fn_jumlah_presensi */
    public static function jumlahStatus(int $idSiswa, string $status): int
    {
        $r = DB::selectOne('SELECT fn_jumlah_presensi(?, ?) AS jumlah', [$idSiswa, $status]);

        return $r ? (int) $r->jumlah : 0;
    }
}
