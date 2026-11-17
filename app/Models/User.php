<?php

namespace App\Models;

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
        return ['is_active' => 'boolean', 'last_login' => 'datetime'];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // Tabel users tidak punya kolom remember_token
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'id_user', 'id_user');
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'id_user', 'id_user');
    }

    public function orangTua()
    {
        return $this->hasOne(OrangTua::class, 'id_user', 'id_user');
    }
}
