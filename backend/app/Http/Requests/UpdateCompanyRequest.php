<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implementar autenticação/autorização
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = $this->route('company');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'document' => [
                'nullable',
                'string',
                'max:18',
                Rule::unique('companies', 'document')->ignore($companyId)
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'business_hours' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_simultaneous_chats' => ['nullable', 'integer', 'min:1'],
            'required_fields' => ['nullable', 'array'],
            'api_endpoint' => ['nullable', 'url', 'max:500'],
            'api_method' => ['nullable', 'in:POST,PUT,PATCH'],
            'api_headers' => ['nullable', 'array'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_enabled' => ['nullable', 'boolean'],
            'ai_prompt' => ['nullable', 'string'],
            'ai_temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'ai_max_tokens' => ['nullable', 'integer', 'min:50', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'document' => 'documento (CNPJ)',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'whatsapp_number' => 'número WhatsApp',
            'required_fields' => 'campos obrigatórios',
            'api_endpoint' => 'endpoint da API',
            'api_method' => 'método HTTP',
            'api_headers' => 'headers da API',
            'api_key' => 'chave da API',
            'api_enabled' => 'API habilitada',
            'ai_prompt' => 'prompt da IA',
            'ai_temperature' => 'temperatura da IA',
            'ai_max_tokens' => 'máximo de tokens',
            'active' => 'ativa',
            'notes' => 'observações',
        ];
    }
}
