<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('imports:prune')->daily();
        $schedule->command('invoices:expire-unpaid')->daily();
        $schedule->command('competition:backup-meet-day')->dailyAt('01:30');
        $schedule->command('queue:prune-failed --hours=168')->weekly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e): void {
            if (! app()->runningInConsole()) {
                /** @var Request|null $request */
                $request = request();
                Log::error($e->getMessage(), [
                    'exception' => $e::class,
                    'user_id' => $request?->user()?->id,
                    'url' => $request?->fullUrl(),
                    'ip' => $request?->ip(),
                ]);
            }
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->expectsJson() || $request->is('health');
        });
    })->create();
