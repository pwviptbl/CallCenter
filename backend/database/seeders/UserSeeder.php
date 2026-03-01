<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('admin123'),
            ]
        );

        // Create test user
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Usuário Teste',
                'password' => bcrypt('user123'),
            ]
        );

        // Create additional test users
        User::firstOrCreate(
            ['email' => 'operador@example.com'],
            [
                'name' => 'Operador CallCenter',
                'password' => bcrypt('operador123'),
            ]
        );
    }
}
