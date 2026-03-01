<?php

namespace App\Services;

use App\Models\UrgencyKeyword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class UrgencyFilter
{
    private const CACHE_KEY = 'urgency_keywords';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Analyze text for urgency keywords.
     *
     * @param string $text
     * @param int|null $companyId
     * @return array{is_urgent: bool, matched_keywords: array, priority_level: int}
     */
    public function analyze(string $text, ?int $companyId = null): array
    {
        $keywords = $this->getKeywords($companyId);
        $matchedKeywords = [];
        $maxPriority = 0;

        foreach ($keywords as $keyword) {
            if ($keyword->matches($text)) {
                $matchedKeywords[] = [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'description' => $keyword->description,
                    'priority_level' => $keyword->priority_level,
                ];

                $maxPriority = max($maxPriority, $keyword->priority_level);
            }
        }

        return [
            'is_urgent' => count($matchedKeywords) > 0,
            'matched_keywords' => $matchedKeywords,
            'priority_level' => $maxPriority,
        ];
    }

    /**
     * Get active keywords for a company (including global keywords).
     */
    private function getKeywords(?int $companyId): Collection
    {
        $cacheKey = self::CACHE_KEY . ($companyId ? "_{$companyId}" : '_global');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            return UrgencyKeyword::active()
                ->forCompany($companyId)
                ->orderBy('priority_level', 'desc')
                ->get();
        });
    }

    /**
     * Clear keywords cache.
     */
    public function clearCache(?int $companyId = null): void
    {
        if ($companyId) {
            Cache::forget(self::CACHE_KEY . "_{$companyId}");
            Cache::forget(self::CACHE_KEY . '_global');
        } else {
            // Clear all urgency keyword caches
            Cache::flush(); // Simplificado - em produção usar tags
        }
    }

    /**
     * Test a keyword pattern against sample text.
     */
    public function testKeyword(string $keyword, string $matchType, string $text, bool $caseSensitive = false, bool $wholeWord = false): bool
    {
        $tempKeyword = new UrgencyKeyword([
            'keyword' => $keyword,
            'match_type' => $matchType,
            'case_sensitive' => $caseSensitive,
            'whole_word' => $wholeWord,
        ]);

        return $tempKeyword->matches($text);
    }
}
