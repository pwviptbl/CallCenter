<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    use HasFactory;

    // Status
    const STATUS_PENDING           = 'pending';
    const STATUS_AI_COLLECTING     = 'ai_collecting';
    const STATUS_AWAITING_REVIEW   = 'awaiting_review';
    const STATUS_IN_PROGRESS       = 'in_progress';
    const STATUS_CONFIRMED_MANUAL  = 'confirmed_manual';
    const STATUS_SENT_API          = 'sent_api';
    const STATUS_RESOLVED          = 'resolved';
    const STATUS_FAILED            = 'failed';

    // Urgência
    const URGENCY_NORMAL   = 'normal';
    const URGENCY_URGENT   = 'urgent';
    const URGENCY_CRITICAL = 'critical';

    // Canal
    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_VOIP     = 'voip';
    const CHANNEL_MANUAL   = 'manual';

    protected $fillable = [
        'company_id',
        'whatsapp_instance_id',
        'attendant_id',
        'contact_name',
        'contact_phone',
        'contact_message',
        'status',
        'urgency_level',
        'urgency_keywords',
        'channel',
        'collected_data',
        'api_response',
        'api_sent_at',
        'api_attempts',
        'external_ticket_id',
        'attended_at',
        'resolved_at',
        'notes',
    ];

    protected $casts = [
        'urgency_keywords' => 'array',
        'collected_data'   => 'array',
        'api_response'     => 'array',
        'api_sent_at'      => 'datetime',
        'attended_at'      => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    // ── Relações ─────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function whatsappInstance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class);
    }

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendant_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_RESOLVED,
            self::STATUS_FAILED,
        ]);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency_level', [
            self::URGENCY_URGENT,
            self::URGENCY_CRITICAL,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isUrgent(): bool
    {
        return $this->urgency_level === self::URGENCY_URGENT
            || $this->urgency_level === self::URGENCY_CRITICAL;
    }

    public function isCritical(): bool
    {
        return $this->urgency_level === self::URGENCY_CRITICAL;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_FAILED]);
    }
}
