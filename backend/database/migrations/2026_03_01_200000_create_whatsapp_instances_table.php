<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('instance_key')->unique()->comment('Nome da instância no Evolution API');
            $table->enum('status', ['disconnected', 'qr_required', 'connecting', 'connected'])
                  ->default('disconnected');
            $table->string('phone_number')->nullable();
            $table->string('evolution_api_url')->comment('URL do Evolution API');
            $table->string('evolution_api_token')->comment('Token da instância no Evolution API');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_instances');
    }
};
