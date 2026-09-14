<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

final class ListPaginator
{
    public const PER_PAGE = 20;

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param  Collection<TKey, TValue>|array<TKey, TValue>  $items
     * @return LengthAwarePaginator<int, TValue>
     */
    public static function for(
        Collection|array $items,
        int $perPage = self::PER_PAGE,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        $collection = Collection::make($items)->values();
        $page = Paginator::resolveCurrentPage($pageName);

        /** @var LengthAwarePaginator<int, TValue> $paginator */
        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );

        return $paginator->withQueryString();
    }
}
