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
        Schema::create('urgency_keywords', function (Blueprint $table) {
            $table->id();
            
            // Palavra-chave ou padrão regex
            $table->string('keyword');
            
            // Tipo: 'exact' (match exato), 'contains' (substring), 'regex' (padrão regex)
            $table->enum('match_type', ['exact', 'contains', 'regex'])->default('contains');
            
            // Descrição da keyword (ex: "Pessoa presa no elevador")
            $table->string('description')->nullable();
            
            // Nível de urgência (1-5, onde 5 é mais urgente)
            $table->tinyInteger('priority_level')->default(5);
            
            // Empresa específica (null = válido para todas)
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            
            // Case sensitive?
            $table->boolean('case_sensitive')->default(false);
            
            // Palavra inteira apenas? (ex: "fogo" não match em "afogado")
            $table->boolean('whole_word')->default(false);
            
            // Status
            $table->boolean('active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['active', 'company_id']);
            $table->index('priority_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urgency_keywords');
    }
};
