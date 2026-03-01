<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrgencyKeyword extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'keyword',
        'match_type',
        'description',
        'priority_level',
        'company_id',
        'case_sensitive',
        'whole_word',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'priority_level' => 'integer',
        'company_id' => 'integer',
        'case_sensitive' => 'boolean',
        'whole_word' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the keyword.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include active keywords.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include global keywords (company_id is null).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    /**
     * Scope a query for a specific company or global keywords.
     */
    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id')
              ->when($companyId, fn($query) => $query->orWhere('company_id', $companyId));
        });
    }

    /**
     * Check if text matches this keyword.
     */
    public function matches(string $text): bool
    {
        $pattern = $this->keyword;
        $subject = $this->case_sensitive ? $text : mb_strtolower($text);
        
        if (!$this->case_sensitive) {
            $pattern = mb_strtolower($pattern);
        }

        return match ($this->match_type) {
            'exact' => $subject === $pattern,
            'contains' => $this->whole_word 
                ? preg_match('/\b' . preg_quote($pattern, '/') . '\b/u', $subject) === 1
                : str_contains($subject, $pattern),
            'regex' => @preg_match('/' . $pattern . '/u', $subject) === 1,
            default => false,
        };
    }
}
