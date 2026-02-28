<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'whatsapp_number',
        'business_hours',
        'timezone',
        'max_users',
        'max_simultaneous_chats',
        'required_fields',
        'api_endpoint',
        'api_method',
        'api_headers',
        'api_key',
        'api_enabled',
        'ai_prompt',
        'ai_temperature',
        'ai_max_tokens',
        'active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'required_fields' => 'array',
        'api_headers' => 'encrypted:array',
        'api_key' => 'encrypted',
        'api_enabled' => 'boolean',
        'ai_temperature' => 'decimal:2',
        'ai_max_tokens' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key',
        'api_headers',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
            'api_headers' => 'encrypted:array',
            'api_key' => 'encrypted',
            'api_enabled' => 'boolean',
            'ai_temperature' => 'decimal:2',
            'ai_max_tokens' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include companies with API enabled.
     */
    public function scopeApiEnabled($query)
    {
        return $query->where('api_enabled', true)->whereNotNull('api_endpoint');
    }
}
