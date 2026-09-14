<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Errors;

interface Error
{
    public function code(): string;

    public function message(): string;

    public function category(): ErrorCategory;

    /**
     * @return array<string, mixed>
     */
    public function details(): array;
}
