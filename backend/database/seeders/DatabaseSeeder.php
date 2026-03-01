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
    }
}
