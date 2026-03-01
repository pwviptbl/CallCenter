<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    const ROLE_ADMIN     = 'admin';
    const ROLE_ATTENDANT = 'attendant';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'company_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function attendingRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'attendant_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // ─── Role helpers ──────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAttendant(): bool
    {
        return $this->role === self::ROLE_ATTENDANT;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
