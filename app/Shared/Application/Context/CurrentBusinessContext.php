<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Context;

use LogicException;

final class CurrentBusinessContext implements BusinessContext
{
    private ?string $businessId = null;

    private ?string $branchId = null;

    public function setBusiness(
        string $businessId,
        ?string $branchId = null,
    ): void {
        if ($businessId === '') {
            throw new \InvalidArgumentException(
                'Business ID cannot be empty.'
            );
        }

        if ($branchId === '') {
            throw new \InvalidArgumentException(
                'Branch ID cannot be empty.'
            );
        }

        $this->businessId = $businessId;
        $this->branchId = $branchId;
    }

    public function clear(): void
    {
        $this->businessId = null;
        $this->branchId = null;
    }

    public function businessId(): string
    {
        if ($this->businessId === null) {
            throw new LogicException(
                'Business context has not been initialized.'
            );
        }

        return $this->businessId;
    }

    public function branchId(): ?string
    {
        return $this->branchId;
    }

    public function hasBusiness(): bool
    {
        return $this->businessId !== null;
    }

    public function hasBranch(): bool
    {
        return $this->branchId !== null;
    }
}
