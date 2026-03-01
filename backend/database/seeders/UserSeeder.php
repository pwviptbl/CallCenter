<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── Admin padrão do sistema ────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@callcenter.local'],
            [
                'name'      => 'Administrador',
                'password'  => Hash::make('Admin@123'),
                'role'      => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        // ── Atendente de exemplo ───────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'atendente@callcenter.local'],
            [
                'name'      => 'Atendente Demo',
                'password'  => Hash::make('Atend@123'),
                'role'      => User::ROLE_ATTENDANT,
                'is_active' => true,
            ]
        );
    }
}
