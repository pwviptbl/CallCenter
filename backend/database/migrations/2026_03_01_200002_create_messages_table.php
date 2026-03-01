<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();

            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('sender_type', ['contact', 'attendant', 'ai', 'system']);
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('content');
            $table->string('media_url')->nullable();
            $table->enum('media_type', ['image', 'audio', 'video', 'document'])->nullable();

            // ID da mensagem no Evolution API (para evitar duplicatas e rastrear)
            $table->string('whatsapp_message_id')->nullable()->index();

            $table->boolean('is_read')->default(false);

            $table->timestamps();

            $table->index(['service_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
