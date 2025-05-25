<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Easygo',
            'email' => 'easygowebid@gmail.com',
            'password' => bcrypt('bbcsatuduatiga'),
            'number' => '0000000000',
            'country' => 'Indonesia',
            'province' => 'Unknown',
            'city' => 'Unknown',
            'is_admin' => true,
        ]);
    }
}