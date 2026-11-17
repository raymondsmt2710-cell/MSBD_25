<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    protected $table = 'siswa';
    protected $primaryKey = 'id_siswa';
    public $timestamps = false;
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function anggotaKelas(): HasMany
    {
        return $this->hasMany(AnggotaKelas::class, 'id_siswa', 'id_siswa');
    }

    public function posisiDuduk(): HasMany
    {
        return $this->hasMany(PosisiDuduk::class, 'id_siswa', 'id_siswa');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class, 'id_siswa', 'id_siswa');
    }

    public function orangTua(): BelongsToMany
    {
        return $this->belongsToMany(OrangTua::class, 'orang_tua_siswa', 'id_siswa', 'id_orang_tua')
            ->withPivot(['hubungan', 'is_kontak_utama']);
    }
}
