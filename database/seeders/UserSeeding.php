<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeding extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->where('email', 'user@example.com')->first();
        if (!$user) {
            User::create([
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
            ]);
        }
    }
}
