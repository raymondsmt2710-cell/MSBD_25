<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PengurusKelas extends Model
{
    protected $table = 'pengurus_kelas';
    protected $primaryKey = 'id_pengurus_kelas';
    public $timestamps = false;
    protected $guarded = [];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }

    /** Dipanggil lewat Stored Function fn_jumlah_pengurus_kelas. */
    public static function jumlahUntukKelas(int $idKelas): int
    {
        $hasil = DB::selectOne('SELECT fn_jumlah_pengurus_kelas(?) AS hasil', [$idKelas]);

        return $hasil ? (int) $hasil->hasil : 0;
    }
}
