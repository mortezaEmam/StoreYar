<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Pagination;

final readonly class PageRequest
{
    public function __construct(
        public int $page,
        public int $perPage,
    ) {
        if ($page < 1) {
            throw new \InvalidArgumentException('Page must be >= 1.');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('per_page must be between 1 and 100.');
        }
    }

    public static function fromQuery(mixed $page, mixed $perPage): self
    {
        return new self(
            page: max(1, (int) ($page ?? 1)),
            perPage: min(100, max(1, (int) ($perPage ?? 20))),
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
