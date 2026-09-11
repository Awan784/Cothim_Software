<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::create([
            'email' => 'admin@example.com',
            'password' => 'abc123',
            'name' => 'Admin',
            'show_password' => 'abc123',
            'user_type' => User::TYPE_ADMIN,
            'is_active' => true,
            'permissions' => null,
        ]);
    }
}
