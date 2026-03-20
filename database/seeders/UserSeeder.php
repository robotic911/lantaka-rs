<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {

    Account::create([
        'name' => 'Tricia',
        'Account_Username' => 'admin01',
        'Account_Email' => 'admin@example.com',
        'Account_Password' => Hash::make('password123'),
        'Account_Role' => 'admin',
        'status' => 'approved',
        'Account_Phone' => '09123456789',         // Add this
        'affiliation' => 'Staff',          // Add this
        'usertype' => 'Internal',          // Add this
        'valid_id_path' => 'ids/default.jpg', // Add this
    ]);

    Account::create([
        'name' => 'Suzette',
        'Account_Username' => 'staff01',
        'Account_Email' => 'staff@example.com',
        'Account_Password' => Hash::make('password123'),
        'Account_Role' => 'staff',
        'status' => 'approved',
        'Account_Phone' => '09987654321',         // Add this
        'affiliation' => 'Staff',          // Add this
        'usertype' => 'Internal',          // Add this
        'valid_id_path' => 'ids/default.jpg', // Add this
    ]);


    }
}
