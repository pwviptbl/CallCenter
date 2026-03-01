<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendant_id')->nullable()->constrained('users')->nullOnDelete();

            // Dados do contato
            $table->string('contact_name')->nullable();
            $table->string('contact_phone');
            $table->text('contact_message')->nullable()->comment('Primeira mensagem recebida');

            // Status e urgência
            $table->enum('status', [
                'pending',           // aguardando processamento
                'ai_collecting',     // IA coletando dados
                'awaiting_review',   // dados coletados, aguardando atendente revisar
                'in_progress',       // atendente assumiu
                'confirmed_manual',  // atendente confirmou abertura manual
                'sent_api',          // enviado via API automaticamente
                'resolved',          // resolvido
                'failed',            // falha (API não respondeu, etc.)
            ])->default('pending');

            $table->enum('urgency_level', ['normal', 'urgent', 'critical'])->default('normal');
            $table->json('urgency_keywords')->nullable()->comment('Keywords que ativaram a urgência');

            // Canal
            $table->enum('channel', ['whatsapp', 'voip', 'manual'])->default('whatsapp');

            // Dados coletados pela IA
            $table->json('collected_data')->nullable()->comment('Campos coletados durante atendimento');

            // Integração com sistema externo
            $table->json('api_response')->nullable()->comment('Resposta da API externa');
            $table->timestamp('api_sent_at')->nullable();
            $table->unsignedTinyInteger('api_attempts')->default(0);
            $table->string('external_ticket_id')->nullable()->comment('ID do chamado no sistema externo');

            // Timestamps de eventos
            $table->timestamp('attended_at')->nullable()->comment('Quando atendente assumiu');
            $table->timestamp('resolved_at')->nullable();

            $table->text('notes')->nullable()->comment('Observações do atendente');

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['urgency_level', 'status']);
            $table->index(['contact_phone']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
