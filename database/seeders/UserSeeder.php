<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'adminTeste',
            'email' => 'admin@serverhub.com',
            'password' => Hash::make('admin@admin'),
            'is_admin' => true,
        ]);

        User::create([
            'name' => 'test1',
            'email' => 'test1@serverhub.com',
            'password' => Hash::make('test1'),
            'is_admin' => false,
        ]);
    }
}
