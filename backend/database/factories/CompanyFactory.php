<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name'                   => $this->faker->company(),
            'document'               => $this->faker->numerify('##############'),
            'email'                  => $this->faker->companyEmail(),
            'phone'                  => $this->faker->numerify('11#########'),
            'address'                => $this->faker->streetAddress(),
            'city'                   => $this->faker->city(),
            'state'                  => 'SP',
            'zip_code'               => $this->faker->numerify('#####-###'),
            'whatsapp_number'        => $this->faker->numerify('5511#########'),
            'business_hours'         => '08:00-18:00',
            'timezone'               => 'America/Sao_Paulo',
            'max_users'              => 10,
            'max_simultaneous_chats' => 5,
            'required_fields'        => ['nome_completo', 'descricao_problema'],
            'api_endpoint'           => null,
            'api_method'             => 'POST',
            'api_headers'            => null,
            'api_key'                => null,
            'api_enabled'            => false,
            'ai_prompt'              => null,
            'ai_temperature'         => 0.7,
            'ai_max_tokens'          => 500,
            'active'                 => true,
            'notes'                  => null,
        ];
    }

    public function withApi(string $endpoint = 'https://erp.example.com/chamado'): static
    {
        return $this->state([
            'api_enabled'  => true,
            'api_endpoint' => $endpoint,
            'api_method'   => 'POST',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
