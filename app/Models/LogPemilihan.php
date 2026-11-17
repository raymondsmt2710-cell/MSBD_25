<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Audit pemilihan. Diisi oleh trigger, tidak pernah ditulis aplikasi.
class LogPemilihan extends Model
{
    protected $table = 'log_pemilihan';
    protected $primaryKey = 'id_log';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['dibuat_pada' => 'datetime'];
    }

    public function pemilihan()
    {
        return $this->belongsTo(Pemilihan::class, 'id_pemilihan', 'id_pemilihan');
    }
}
