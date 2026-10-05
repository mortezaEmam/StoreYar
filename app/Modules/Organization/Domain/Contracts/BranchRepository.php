<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\Contracts;

use StoreYar\Modules\Organization\Domain\Entities\Branch;
use StoreYar\Modules\Organization\Domain\ValueObjects\BranchId;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;

interface BranchRepository
{
    public function findById(BranchId $id): ?Branch;

    /**
     * @return list<Branch>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;

    public function save(Branch $branch): void;
}
