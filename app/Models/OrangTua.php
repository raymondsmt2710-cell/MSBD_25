<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrangTua extends Model
{
    protected $table = 'orang_tua';
    protected $primaryKey = 'id_orang_tua';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /** Anak-anak dari orang tua ini (relasi banyak-ke-banyak lewat orang_tua_siswa). */
    public function anak()
    {
        return $this->belongsToMany(Siswa::class, 'orang_tua_siswa', 'id_orang_tua', 'id_siswa')
            ->withPivot(['hubungan', 'is_kontak_utama']);
    }
}
