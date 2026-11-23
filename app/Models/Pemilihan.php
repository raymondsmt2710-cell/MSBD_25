<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pemilihan extends Model
{
    protected $table = 'pemilihan';
    protected $primaryKey = 'id_pemilihan';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function kandidat()
    {
        return $this->hasMany(Kandidat::class, 'id_pemilihan', 'id_pemilihan');
    }

    public function pemilih()
    {
        return $this->hasMany(DaftarPemilih::class, 'id_pemilihan', 'id_pemilihan');
    }

    public function panitia()
    {
        return $this->hasMany(PanitiaPemilihan::class, 'id_pemilihan', 'id_pemilihan');
    }

    public function suara()
    {
        return $this->hasMany(Suara::class, 'id_pemilihan', 'id_pemilihan');
    }

    public function scopeBerlangsung($query)
    {
        return $query->where('status', 'berlangsung');
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    public function scopeDraf($query)
    {
        return $query->where('status', 'draf');
    }


    public static function sudahMemilih(int $idPemilihan, int $idSiswa): bool
    {
        $hasil = DB::selectOne('SELECT fn_sudah_memilih(?, ?) AS hasil', [$idPemilihan, $idSiswa]);

        return $hasil && (int) $hasil->hasil === 1;
    }


    public static function bolehMemilih(int $idPemilihan, int $idSiswa): bool
    {
        $hasil = DB::selectOne('SELECT fn_boleh_memilih(?, ?) AS hasil', [$idPemilihan, $idSiswa]);

        return $hasil && (int) $hasil->hasil === 1;
    }


    public static function totalSuara(int $idPemilihan): int
    {
        $hasil = DB::selectOne('SELECT fn_total_suara(?) AS hasil', [$idPemilihan]);

        return $hasil ? (int) $hasil->hasil : 0;
    }


    public static function partisipasi(int $idPemilihan): float
    {
        $hasil = DB::selectOne('SELECT fn_partisipasi(?) AS hasil', [$idPemilihan]);

        return $hasil ? (float) $hasil->hasil : 0.0;
    }
}
