<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUsersSeeder extends Seeder
{
    public function run(): void
    {
        $need = max(0, 10 - User::where('role', 'user')->count());

        User::factory()
            ->count($need)
            ->create([
                'role' => 'user',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
    }
}