<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

// Disesuaikan dengan tabel `users` proyek (id_user, username, password_hash, role)
class User extends Authenticatable
{
    protected $table = 'users';
    protected $primaryKey = 'id_user';
    protected $fillable = ['username', 'password_hash', 'role', 'is_active', 'last_login'];
    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'is_active' => 'boolean',
            'last_login' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Nama kolom password. Tanpa ini Laravel memakai 'password' saat perlu
     * me-rehash (mis. cost bcrypt berubah) dan menulis ke kolom yang tidak ada.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    // Tabel users tidak punya kolom remember_token
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class, 'id_user', 'id_user');
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class, 'id_user', 'id_user');
    }

    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class, 'id_user', 'id_user');
    }
}
