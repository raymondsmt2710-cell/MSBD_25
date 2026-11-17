<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKas extends Model
{
    protected $table = 'transaksi_kas';

    protected $primaryKey = 'id_transaksi';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'nominal' => 'decimal:2', 'is_batal' => 'boolean'];
    }
}
