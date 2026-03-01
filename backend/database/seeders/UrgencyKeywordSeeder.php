<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UrgencyKeyword;

class UrgencyKeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keywords = config('urgency.default_keywords', []);

        foreach ($keywords as $keyword) {
            UrgencyKeyword::firstOrCreate(
                [
                    'keyword' => $keyword['keyword'],
                    'match_type' => $keyword['match_type'],
                ],
                [
                    'description' => $keyword['description'] ?? null,
                    'priority_level' => $keyword['priority_level'] ?? 4,
                    'case_sensitive' => $keyword['case_sensitive'] ?? false,
                    'whole_word' => $keyword['whole_word'] ?? false,
                    'active' => true,
                    'company_id' => null, // Global keyword
                ]
            );
        }

        $this->command->info('Urgency keywords seeded successfully!');
    }
}
