<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $timestamps = false;
    protected $guarded = [];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_wali_kelas', 'id_guru');
    }

    public function anggotaKelas(): HasMany
    {
        return $this->hasMany(AnggotaKelas::class, 'id_kelas', 'id_kelas');
    }

    public function jadwalPelajaran(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'id_kelas', 'id_kelas');
    }

    public function denahKelas(): HasMany
    {
        return $this->hasMany(DenahKelas::class, 'id_kelas', 'id_kelas');
    }

    public function pengurusKelas(): HasMany
    {
        return $this->hasMany(PengurusKelas::class, 'id_kelas', 'id_kelas');
    }
}
