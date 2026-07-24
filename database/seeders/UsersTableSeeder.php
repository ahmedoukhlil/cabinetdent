<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Administrateur
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@cabinet.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Secrétaire
        DB::table('users')->insert([
            'name' => 'Secrétaire',
            'email' => 'secretaire@cabinet.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Assistant(e)
        DB::table('users')->insert([
            'name' => 'Assistant',
            'email' => 'assistant@cabinet.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
