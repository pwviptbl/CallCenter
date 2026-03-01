<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappInstance extends Model
{
    use HasFactory;

    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_QR_REQUIRED  = 'qr_required';
    const STATUS_CONNECTING   = 'connecting';
    const STATUS_CONNECTED    = 'connected';

    protected $fillable = [
        'company_id',
        'name',
        'instance_key',
        'status',
        'phone_number',
        'evolution_api_url',
        'evolution_api_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'evolution_api_token',
    ];

    // ── Relações ─────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
    }
}
