<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Context;

interface BusinessContext
{
    public function businessId(): string;

    public function branchId(): ?string;

    public function hasBusiness(): bool;

    public function hasBranch(): bool;
}
