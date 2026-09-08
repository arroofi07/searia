<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Fail the build when a new mutating web route is added without auth.
 */
it('requires auth middleware on every mutating web route', function () {
    // Pendaftaran peserta memang terbuka tanpa akun. Sebagai gantinya setiap rute
    // publik yang mengubah data wajib punya rate limiter, dicek terpisah di bawah.
    $publicByDesign = [
        'login',
        'register.athlete',
        'register.events.store',
        'register.store',
        'register.parse-time',
    ];

    $checked = 0;
    $unprotected = [];

    foreach (Route::getRoutes() as $route) {
        /** @var RoutingRoute $route */
        $methods = array_diff($route->methods(), ['HEAD']);
        if (! array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            continue;
        }

        $name = $route->getName() ?? $route->uri();
        if (in_array($name, $publicByDesign, true)) {
            continue;
        }

        $middleware = $route->gatherMiddleware();
        if (! in_array('auth', $middleware, true)) {
            $unprotected[] = $name;
        }
        $checked++;
    }

    expect($unprotected)->toBeEmpty()
        ->and($checked)->toBeGreaterThan(10);
});

it('rate limits every public mutating route', function () {
    $unlimited = [];

    foreach (['register.athlete', 'register.store', 'register.parse-time'] as $name) {
        $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();

        $throttled = collect($middleware)
            ->contains(fn (mixed $item): bool => is_string($item) && str_starts_with($item, 'throttle:'));

        if (! $throttled) {
            $unlimited[] = $name;
        }
    }

    expect($unlimited)->toBeEmpty();
});

it('keeps public read routes reachable without authentication', function () {
    expect(route('home'))->toBeString()
        ->and(route('archive.index'))->toBeString()
        ->and(route('health'))->toBeString()
        ->and(route('certificates.verify', ['code' => 'EXAMPLE']))->toBeString();

    $this->get(route('home'))->assertOk();
    $this->get(route('health'))->assertOk();
});
