<?php

namespace App\Services;

use App\Models\Athlete;
use Illuminate\Support\Collection;

class AthleteMatcher
{
    public function __construct(
        private readonly ?float $threshold = null,
    ) {}

    public function threshold(): float
    {
        return $this->threshold ?? (float) config('searia.athlete_name_similarity_threshold', 0.78);
    }

    public function areSimilar(string $left, string $right): bool
    {
        return $this->similarity($left, $right) >= $this->threshold();
    }

    public function similarity(string $left, string $right): float
    {
        $left = $this->normalize($left);
        $right = $this->normalize($right);

        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 1.0;
        }

        similar_text($left, $right, $percent);
        $similarText = $percent / 100;

        $maxLength = max(mb_strlen($left), mb_strlen($right));
        $levenshtein = $maxLength > 0
            ? 1 - (levenshtein($left, $right) / $maxLength)
            : 0.0;

        return max($similarText, $levenshtein, $this->tokenSimilarity($left, $right));
    }

    /**
     * @return Collection<int, Athlete>
     */
    public function findSimilar(Athlete $athlete): Collection
    {
        return Athlete::query()
            ->where('club_id', $athlete->club_id)
            ->where('birth_year', $athlete->birth_year)
            ->where('id', '!=', $athlete->id)
            ->get()
            ->filter(fn (Athlete $other): bool => $this->areSimilar($athlete->full_name, $other->full_name))
            ->values();
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $value);

        return is_string($normalized) ? $normalized : $value;
    }

    private function tokenSimilarity(string $left, string $right): float
    {
        $leftTokens = explode(' ', $left);
        $rightTokens = explode(' ', $right);

        if ($leftTokens === [] || $rightTokens === []) {
            return 0.0;
        }

        $matched = 0;

        foreach ($leftTokens as $token) {
            foreach ($rightTokens as $candidate) {
                if ($this->tokensMatch($token, $candidate)) {
                    $matched++;
                    break;
                }
            }
        }

        return $matched / max(count($leftTokens), count($rightTokens));
    }

    private function tokensMatch(string $left, string $right): bool
    {
        if ($left === $right) {
            return true;
        }

        if (str_starts_with($right, $left) || str_starts_with($left, $right)) {
            return true;
        }

        $shorter = mb_strlen($left) <= mb_strlen($right) ? $left : $right;
        $longer = $shorter === $left ? $right : $left;

        if (mb_strlen($shorter) < 3) {
            return false;
        }

        $position = 0;

        for ($index = 0, $length = mb_strlen($shorter); $index < $length; $index++) {
            $found = mb_strpos($longer, mb_substr($shorter, $index, 1), $position);

            if ($found === false) {
                return false;
            }

            $position = $found + 1;
        }

        return true;
    }
}
