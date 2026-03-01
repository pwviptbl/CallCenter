<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WhatsappInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappInstanceFactory extends Factory
{
    protected $model = WhatsappInstance::class;

    public function definition(): array
    {
        return [
            'company_id'          => Company::factory(),
            'name'                => $this->faker->words(2, true),
            'instance_key'        => $this->faker->slug(2),
            'evolution_api_url'   => 'http://evolution-api:8081',
            'evolution_api_token' => $this->faker->uuid(),
            'phone_number'        => null,
            'status'              => 'disconnected',
            'is_active'           => true,
        ];
    }

    public function connected(): static
    {
        return $this->state([
            'status'       => 'connected',
            'phone_number' => '5511999990000',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
