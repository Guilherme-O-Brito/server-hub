<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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
