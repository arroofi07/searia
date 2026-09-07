<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Fail the build when a new mutating web route is added without auth.
 */
it('requires auth middleware on every mutating web route', function () {
    $exceptions = [
        'login',
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
        if (in_array($name, $exceptions, true)) {
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

it('keeps public read routes reachable without authentication', function () {
    expect(route('home'))->toBeString()
        ->and(route('archive.index'))->toBeString()
        ->and(route('health'))->toBeString()
        ->and(route('certificates.verify', ['code' => 'EXAMPLE']))->toBeString();

    $this->get(route('home'))->assertOk();
    $this->get(route('health'))->assertOk();
});
