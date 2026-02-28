<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            
            // Dados gerais
            $table->string('name');
            $table->string('document')->unique()->nullable(); // CNPJ
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            // Endereço
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 10)->nullable();
            
            // WhatsApp
            $table->string('whatsapp_number')->nullable();
            
            // Configurações de atendimento
            $table->string('business_hours')->default('08:00-18:00');
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->integer('max_users')->default(10);
            $table->integer('max_simultaneous_chats')->default(5);
            
            // Campos obrigatórios configuráveis (JSONB)
            // Exemplo: ["nome_solicitante", "local", "andar", "elevador"]
            $table->jsonb('required_fields')->default('[]');
            
            // Configuração de API para integração (Modo 1)
            $table->string('api_endpoint')->nullable();
            $table->enum('api_method', ['POST', 'PUT', 'PATCH'])->default('POST');
            $table->text('api_headers')->nullable(); // Encrypted JSON
            $table->text('api_key')->nullable(); // Encrypted
            $table->boolean('api_enabled')->default(false);
            
            // Configuração de IA
            $table->text('ai_prompt')->nullable(); // Prompt customizado
            $table->decimal('ai_temperature', 3, 2)->default(0.7);
            $table->integer('ai_max_tokens')->default(500);
            
            // Status
            $table->boolean('active')->default(true);
            
            // Metadata
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('active');
            $table->index('whatsapp_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
