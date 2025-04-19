<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class OwnerUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Budi',
            'email' => 'insannurul005@gmail.com',
            'password' => Hash::make('budi123'),
            'role' => 'owner',
        ]);
    }
}
