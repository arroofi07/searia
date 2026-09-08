<?php

namespace App\Http\Middleware;

use App\Support\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
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

        /** @var array{status: int, content: string, headers: array<string, list<string|null>>}|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['status'], $cached['content'], $cached['headers'])) {
            return $this->restore($cached);
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->getStatusCode() === 200 && ! $response->isRedirection() && $this->isCacheable($response)) {
            Cache::put($cacheKey, [
                'status' => $response->getStatusCode(),
                'content' => (string) $response->getContent(),
                'headers' => $response->headers->all(),
            ], $ttlSeconds);
        }

        return $response;
    }

    private function isCacheable(Response $response): bool
    {
        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html')
            || str_contains($contentType, 'application/xml')
            || str_contains($contentType, 'text/xml')
            || $contentType === '';
    }

    /**
     * @param  array{status: int, content: string, headers: array<string, list<string|null>>}  $cached
     */
    private function restore(array $cached): Response
    {
        $response = new IlluminateResponse($cached['content'], $cached['status']);

        foreach ($cached['headers'] as $name => $values) {
            $normalized = strtolower((string) $name);
            if (in_array($normalized, ['set-cookie', 'cookie'], true)) {
                continue;
            }

            $response->headers->set($name, $values);
        }

        return $response;
    }
}
