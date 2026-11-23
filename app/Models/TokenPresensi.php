<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TokenPresensi extends Model
{
    protected $table = 'token_presensi';
    protected $primaryKey = 'id_token';
    public $timestamps = false;
    protected $guarded = [];


    public static function masihValid(string $kode): bool
    {
        $hasil = DB::selectOne('SELECT fn_token_valid(?) AS ok', [$kode]);

        return $hasil && (int) $hasil->ok === 1;
    }
}
