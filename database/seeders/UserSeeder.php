<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
   
    User::create([
        'name' => 'Tricia',
        'username' => 'admin01',
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'status' => 'approved',
        'phone' => '09123456789',         // Add this
        'affiliation' => 'Staff',          // Add this
        'usertype' => 'Internal',          // Add this
        'valid_id_path' => 'ids/default.jpg', // Add this
    ]);

    User::create([
        'name' => 'Suzette',
        'username' => 'staff01',
        'email' => 'staff@example.com',
        'password' => Hash::make('password123'),
        'role' => 'staff',
        'status' => 'approved',
        'phone' => '09987654321',         // Add this
        'affiliation' => 'Staff',          // Add this
        'usertype' => 'Internal',          // Add this
        'valid_id_path' => 'ids/default.jpg', // Add this
    ]);


    }
}