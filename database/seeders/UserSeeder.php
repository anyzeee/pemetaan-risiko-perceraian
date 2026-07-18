<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@dp3akb.kalselprov.go.id'],
            [
                'name'     => 'Admin DP3AKB',
                'role'     => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator@dp3akb.kalselprov.go.id'],
            [
                'name'     => 'Operator DP3AKB',
                'role'     => 'operator',
                'password' => Hash::make('password'),
            ]
        );
    }
}