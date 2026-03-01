<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed users first (before companies)
        $this->call([
            UserSeeder::class,
        ]);

        // Seed companies and keywords
        $this->call([
            CompanySeeder::class,
            UrgencyKeywordSeeder::class,
        ]);

        // Associa usuários padrão à primeira empresa (se ainda sem empresa)
        $firstCompany = \App\Models\Company::first();
        if ($firstCompany) {
            \App\Models\User::whereNull('company_id')
                ->whereIn('email', ['admin@callcenter.local', 'atendente@callcenter.local'])
                ->update(['company_id' => $firstCompany->id]);
        }

        // Seed service requests and messages demo
        $this->call([
            ServiceRequestSeeder::class,
        ]);
    }
}
