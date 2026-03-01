<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'attendant'])->default('attendant')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete()->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['role', 'is_active', 'company_id', 'last_login_at']);
        });
    }
};
