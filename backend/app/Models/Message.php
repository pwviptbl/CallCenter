<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    const DIRECTION_INBOUND  = 'inbound';
    const DIRECTION_OUTBOUND = 'outbound';

    const SENDER_CONTACT   = 'contact';
    const SENDER_ATTENDANT = 'attendant';
    const SENDER_AI        = 'ai';
    const SENDER_SYSTEM    = 'system';

    const MEDIA_IMAGE    = 'image';
    const MEDIA_AUDIO    = 'audio';
    const MEDIA_VIDEO    = 'video';
    const MEDIA_DOCUMENT = 'document';

    protected $fillable = [
        'service_request_id',
        'direction',
        'sender_type',
        'sender_id',
        'content',
        'media_url',
        'media_type',
        'whatsapp_message_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // ── Relações ─────────────────────────────────────────────────────────────

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false)
                     ->where('direction', self::DIRECTION_INBOUND);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function hasMedia(): bool
    {
        return !empty($this->media_url);
    }

    public function isFromContact(): bool
    {
        return $this->sender_type === self::SENDER_CONTACT;
    }

    public function isFromAI(): bool
    {
        return $this->sender_type === self::SENDER_AI;
    }
}
