<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'role' => Role::Siswa,
            'is_active' => true,
        ];
    }

    public function admin(): static    { return $this->state(['role' => Role::Admin]); }
    public function guru(): static     { return $this->state(['role' => Role::Guru]); }
    public function orangTua(): static { return $this->state(['role' => Role::OrangTua]); }
    public function nonaktif(): static { return $this->state(['is_active' => false]); }
}