<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (['admin', 'guru', 'siswa', 'orang_tua'] as $role) {
            User::create([
                'username' => $role,
                'password_hash' => 'password',
                'role' => $role,
            ]);
        }
    }
}