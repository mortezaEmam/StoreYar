<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\Contracts;

use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;

interface OrganizationRepository
{
    public function findById(OrganizationId $id): ?Organization;

    public function findByName(string $name): ?Organization;

    public function save(Organization $organization): void;
}
