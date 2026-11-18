<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PengurusOrganisasi extends Model
{
    protected $table = 'pengurus_organisasi';
    protected $primaryKey = 'id_pengurus_org';
    public $timestamps = false;
    protected $guarded = [];

    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }

    /** Dipanggil lewat Stored Function fn_jumlah_pengurus_organisasi. */
    public static function jumlahUntukOrganisasi(int $idOrganisasi, int $idTahunAjaran): int
    {
        $hasil = DB::selectOne(
            'SELECT fn_jumlah_pengurus_organisasi(?, ?) AS hasil',
            [$idOrganisasi, $idTahunAjaran]
        );

        return $hasil ? (int) $hasil->hasil : 0;
    }
}
