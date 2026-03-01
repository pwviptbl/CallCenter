<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\UrgencyKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

class UrgencyKeywordFactory extends Factory
{
    protected $model = UrgencyKeyword::class;

    public function definition(): array
    {
        return [
            'company_id'     => null, // null = global
            'keyword'        => $this->faker->word(),
            'match_type'     => 'contains',
            'description'    => $this->faker->sentence(),
            'priority_level' => $this->faker->numberBetween(1, 10),
            'active'         => true,
            'case_sensitive' => false,
            'whole_word'     => false,
        ];
    }

    public function global(): static
    {
        return $this->state(['company_id' => null]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(['company_id' => $company->id]);
    }

    public function critical(): static
    {
        return $this->state(['priority_level' => 10]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function regex(): static
    {
        return $this->state(['match_type' => 'regex']);
    }
}
