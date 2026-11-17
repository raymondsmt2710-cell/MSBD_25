<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    protected $primaryKey = 'id_tahun_ajaran';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'is_aktif' => 'boolean'];
    }

    /** Tahun ajaran yang sedang aktif, atau null bila belum ada yang ditandai. */
    public static function aktif(): ?self
    {
        return static::where('is_aktif', true)->first();
    }
}
