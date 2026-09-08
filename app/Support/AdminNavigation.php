<?php

namespace App\Support;

use App\Models\Competition;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class AdminNavigation
{
    public const SESSION_KEY = 'current_competition_id';

    public static function competition(): ?Competition
    {
        if (request()->attributes->get('admin.navigation.resolved')) {
            $cached = request()->attributes->get('admin.navigation.competition');

            return $cached instanceof Competition ? $cached : null;
        }

        $resolved = self::fromRoute() ?? self::fromSessionOrLatest();
        if ($resolved instanceof Competition) {
            session([self::SESSION_KEY => $resolved->id]);
        }

        request()->attributes->set('admin.navigation.resolved', true);
        request()->attributes->set('admin.navigation.competition', $resolved);

        return $resolved;
    }

    /**
     * @return Collection<int, Competition>
     */
    public static function competitions(): Collection
    {
        return Competition::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'status', 'start_date']);
    }

    public static function url(string $name): string
    {
        $competition = self::competition();
        if ($competition === null) {
            return route('admin.competitions.index');
        }

        return route($name, $competition);
    }

    public static function currentKey(): string
    {
        $name = (string) request()->route()?->getName();

        return match (true) {
            str_starts_with($name, 'admin.imports.'),
            str_starts_with($name, 'admin.registrations.'),
            str_starts_with($name, 'admin.submissions.') => 'registrations',
            str_starts_with($name, 'admin.seeding.') => 'seeding',
            str_starts_with($name, 'admin.start-list.') => 'start-list',
            str_starts_with($name, 'admin.results.'),
            str_starts_with($name, 'admin.exports.'),
            str_starts_with($name, 'admin.judges.'),
            str_starts_with($name, 'certificates.') => 'results',
            $name === 'admin.competitions.index',
            $name === 'admin.competitions.create',
            $name === 'admin.competitions.show',
            $name === 'admin.competitions.edit',
            $name === 'admin.competitions.update',
            str_contains($name, 'age-groups'),
            str_contains($name, 'eligibility'),
            str_contains($name, 'readiness'),
            str_starts_with($name, 'admin.competitions.events.') => 'dasbor',
            default => '',
        };
    }

    public static function linkClass(bool $active): string
    {
        if ($active) {
            return 'flex items-center rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white';
        }

        return 'flex items-center rounded-md px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white';
    }

    private static function fromSessionOrLatest(): ?Competition
    {
        $id = session(self::SESSION_KEY);
        if (is_numeric($id)) {
            $stored = Competition::query()->find($id);
            if ($stored instanceof Competition) {
                return $stored;
            }
        }

        return Competition::query()->latest('id')->first();
    }

    private static function fromRoute(): ?Competition
    {
        $route = request()->route();
        if ($route === null) {
            return null;
        }

        $competition = $route->parameter('competition');
        if ($competition instanceof Competition) {
            return $competition;
        }

        foreach (['importBatch', 'submission', 'registration', 'invoice'] as $parameter) {
            $model = $route->parameter($parameter);
            if ($model instanceof Model && method_exists($model, 'competition')) {
                $related = $model->competition;
                if ($related instanceof Competition) {
                    return $related;
                }
            }
        }

        $heat = $route->parameter('heat');
        if ($heat instanceof Heat) {
            return $heat->event?->competition;
        }

        $lane = $route->parameter('heatLane');
        if ($lane instanceof HeatLane) {
            return $lane->heat?->event?->competition;
        }

        $result = $route->parameter('result');
        if ($result instanceof Result) {
            return $result->heatLane?->heat?->event?->competition;
        }

        return null;
    }
}
