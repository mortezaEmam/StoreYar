<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Pagination;

/**
 * @template T
 */
final readonly class PageResult
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    /**
     * @return array{page: int, per_page: int, total: int, last_page: int}
     */
    public function toMeta(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage(),
        ];
    }
}
