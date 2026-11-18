<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Tabel `jabatan` tidak punya timestamps.
class Jabatan extends Model
{
    protected $table = 'jabatan';
    protected $primaryKey = 'id_jabatan';
    public $timestamps = false;
    protected $guarded = [];

    public function scopeLingkup($query, string $lingkup)
    {
        return $query->where('lingkup', $lingkup);
    }

    public function scopeUrut($query)
    {
        return $query->orderBy('urutan')->orderBy('id_jabatan');
    }
}
