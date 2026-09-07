<?php

namespace App\Http\Middleware;

use App\Support\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPages
{
    public function handle(Request $request, Closure $next, int $ttlSeconds = 300): Response
    {
        if ($request->user() !== null || ! $request->isMethod('GET')) {
            return $next($request);
        }

        $cacheKey = PublicPageCache::key('response:'.sha1($request->fullUrl()));

        /** @var Response|null $cached */
        $cached = Cache::get($cacheKey);
        if ($cached instanceof Response) {
            return $cached;
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->getStatusCode() === 200 && ! $response->isRedirection()) {
            Cache::put($cacheKey, $response, $ttlSeconds);
        }

        return $response;
    }
}
