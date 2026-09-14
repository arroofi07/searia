<?php

namespace App\Services\EventProgram;

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use Illuminate\Support\Collection;

class EventProgramParser
{
    /**
     * @return array{distance: int, stroke: Stroke, equipment: Equipment}|null
     */
    public function parseName(string $name): ?array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        if (! preg_match('/^(\d+)\s*m\s+(.+)$/iu', $normalized, $matches)) {
            return null;
        }

        $distance = (int) $matches[1];

        if (! in_array($distance, [25, 50, 100, 200, 400], true)) {
            return null;
        }

        $rest = trim($matches[2]);
        $equipment = Equipment::None;

        if (preg_match('/\(([^)]+)\)\s*$/u', $rest, $eq)) {
            $parsedEquipment = $this->parseEquipment($eq[1]);

            if ($parsedEquipment === null) {
                return null;
            }

            $equipment = $parsedEquipment;
            $rest = trim((string) preg_replace('/\(([^)]+)\)\s*$/u', '', $rest));
        }

        $stroke = $this->parseStroke($rest);

        if ($stroke === null) {
            return null;
        }

        return [
            'distance' => $distance,
            'stroke' => $stroke,
            'equipment' => $equipment,
        ];
    }

    public function parseGender(string $value): ?EventGender
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['.', '/'], '', $normalized);

        return match ($normalized) {
            'putra', 'pa', 'l', 'laki-laki', 'laki laki', 'male', 'm' => EventGender::Male,
            'putri', 'pi', 'p', 'perempuan', 'female', 'f' => EventGender::Female,
            default => null,
        };
    }

    /**
     * @param  Collection<int, AgeGroup>  $ageGroups
     * @return array{ids: list<int>|null, unknown: list<string>}
     */
    public function parseGroups(string $raw, Collection $ageGroups): array
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return ['ids' => null, 'unknown' => []];
        }

        $parts = preg_split('/[,;\/|]+/u', $trimmed) ?: [];
        $ids = [];
        $unknown = [];
        $index = $this->groupIndex($ageGroups);

        foreach ($parts as $part) {
            $token = trim($part);

            if ($token === '') {
                continue;
            }

            $key = $this->groupKey($token);

            if (isset($index[$key])) {
                $ids[] = $index[$key];

                continue;
            }

            $code = $this->matchDefaultGroupCode($token);

            if ($code !== null && isset($index[$this->groupKey($code)])) {
                $ids[] = $index[$this->groupKey($code)];

                continue;
            }

            $unknown[] = $token;
        }

        return [
            'ids' => array_values(array_unique($ids)),
            'unknown' => $unknown,
        ];
    }

    public function parseEventNumber(string $value): ?int
    {
        $trimmed = trim($value);

        if ($trimmed === '' || ! preg_match('/^\d+$/', $trimmed)) {
            return null;
        }

        $number = (int) $trimmed;

        return $number >= 1 && $number <= 9999 ? $number : null;
    }

    /**
     * @return list<string>
     */
    public function groupTokens(string $raw): array
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return [];
        }

        $parts = preg_split('/[,;\/|]+/u', $trimmed) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            $token = trim($part);

            if ($token !== '') {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    public function matchDefaultGroupCode(string $token): ?string
    {
        $compact = $this->compactGroupKey($token);

        foreach (AgeGroup::defaultDefinitions(2000) as $definition) {
            foreach ($this->defaultGroupAliases($definition) as $alias) {
                if ($this->compactGroupKey($alias) === $compact) {
                    return $definition['code'];
                }
            }
        }

        if (preg_match('/^(.+?)([1-9])$/u', $compact, $matches) !== 1) {
            return null;
        }

        if (preg_match('/^\d+$/u', $matches[1]) === 1) {
            return null;
        }

        return $matches[2];
    }

    public function isDefaultGroupAlias(string $token): bool
    {
        $code = $this->matchDefaultGroupCode($token);

        if ($code === null) {
            return false;
        }

        $compact = $this->compactGroupKey($token);

        foreach (AgeGroup::defaultDefinitions(2000) as $definition) {
            if ($definition['code'] !== $code) {
                continue;
            }

            foreach ($this->defaultGroupAliases($definition) as $alias) {
                if ($this->compactGroupKey($alias) === $compact) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array{code: string, name: string, display_code: string, birth_year_start: int, birth_year_end: int, sort_order: int}  $definition
     * @return list<string>
     */
    private function defaultGroupAliases(array $definition): array
    {
        return [
            $definition['name'],
            $definition['code'],
            $definition['display_code'],
            'group '.$definition['code'],
            'grup '.$definition['code'],
        ];
    }

    private function compactGroupKey(string $value): string
    {
        return str_replace(' ', '', $this->groupKey($value));
    }

    private function parseStroke(string $value): ?Stroke
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['gaya', '-'], ['', ' '], $normalized);
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $normalized));

        return match ($normalized) {
            'kupu kupu', 'kupu', 'butterfly' => Stroke::Butterfly,
            'punggung', 'backstroke', 'back' => Stroke::Backstroke,
            'dada', 'breaststroke', 'breast' => Stroke::Breaststroke,
            'ganti', 'medley', 'im' => Stroke::Medley,
            'bebas', 'freestyle', 'free' => Stroke::Freestyle,
            default => null,
        };
    }

    private function parseEquipment(string $value): ?Equipment
    {
        $normalized = mb_strtolower(trim($value));

        return match ($normalized) {
            '', 'none', 'tanpa', 'tanpa alat' => Equipment::None,
            'fins', 'fin', 'kaki katak' => Equipment::Fins,
            'kickboard', 'kick board', 'papan', 'papan luncur' => Equipment::Kickboard,
            default => null,
        };
    }

    /**
     * @param  Collection<int, AgeGroup>  $ageGroups
     * @return array<string, int>
     */
    private function groupIndex(Collection $ageGroups): array
    {
        $index = [];

        foreach ($ageGroups as $group) {
            foreach ([$group->name, $group->code, $group->display_code, 'group '.$group->code, 'grup '.$group->code] as $label) {
                $index[$this->groupKey((string) $label)] = $group->id;
            }
        }

        return $index;
    }

    private function groupKey(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['-', '_'], ' ', $normalized);

        return trim((string) preg_replace('/\s+/u', ' ', $normalized));
    }
}
