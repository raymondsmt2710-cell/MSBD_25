<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('orang_tua', key: 'id_orang_tua', timestamps: false)]
#[Fillable(['id_user', 'nama', 'no_hp', 'email', 'pekerjaan', 'alamat'])]
class OrangTua extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'orang_tua_siswa', 'id_orang_tua', 'id_siswa')
            ->withPivot(['hubungan', 'is_kontak_utama']);
    }
}
