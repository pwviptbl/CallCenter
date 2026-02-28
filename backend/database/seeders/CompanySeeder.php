<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'name' => 'Empresa Demo LTDA',
            'document' => '12345678901234',
            'email' => 'contato@empresademo.com.br',
            'phone' => '11987654321',
            'whatsapp_number' => '5511987654321',
            'address' => 'Av. Paulista, 1000',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01310-100',
            'business_hours' => '08:00-18:00',
            'timezone' => 'America/Sao_Paulo',
            'max_users' => 50,
            'max_simultaneous_chats' => 100,
            'api_enabled' => true,
            'api_key' => 'demo_api_key_' . bin2hex(random_bytes(16)),
            'api_headers' => json_encode([
                'X-Custom-Header' => 'valor-personalizado',
                'Authorization' => 'Bearer token-exemplo'
            ]),
            'required_fields' => json_encode([
                'name' => true,
                'email' => true,
                'phone' => true,
                'cpf' => false
            ]),
            'active' => true,
        ]);

        Company::create([
            'name' => 'Tech Solutions Brasil',
            'document' => '98765432109876',
            'email' => 'suporte@techsolutions.com.br',
            'phone' => '21976543210',
            'whatsapp_number' => '5521976543210',
            'address' => 'Rua das Flores, 500',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
            'zip_code' => '20000-000',
            'business_hours' => '09:00-17:00',
            'timezone' => 'America/Sao_Paulo',
            'max_users' => 25,
            'max_simultaneous_chats' => 50,
            'api_enabled' => true,
            'api_key' => 'tech_api_key_' . bin2hex(random_bytes(16)),
            'api_headers' => json_encode([
                'X-API-Version' => 'v1',
                'Content-Type' => 'application/json'
            ]),
            'required_fields' => json_encode([
                'name' => true,
                'email' => true,
                'phone' => false,
                'document' => true
            ]),
            'active' => true,
        ]);

        Company::create([
            'name' => 'Startup Inovadora',
            'document' => '11122233344455',
            'email' => 'contato@startupinovadora.com',
            'phone' => '47988776655',
            'whatsapp_number' => '5547988776655',
            'address' => 'Av. Beira Mar, 200',
            'city' => 'Florianópolis',
            'state' => 'SC',
            'zip_code' => '88000-000',
            'business_hours' => '10:00-19:00',
            'timezone' => 'America/Sao_Paulo',
            'max_users' => 10,
            'max_simultaneous_chats' => 20,
            'api_enabled' => false,
            'api_key' => null,
            'api_headers' => null,
            'required_fields' => json_encode([
                'name' => true,
                'email' => true,
                'phone' => true
            ]),
            'active' => true,
        ]);
    }
}
