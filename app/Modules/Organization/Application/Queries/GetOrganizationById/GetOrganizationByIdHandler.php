<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Queries\GetOrganizationById;

use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class GetOrganizationByIdHandler implements QueryHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(Query $query): ?Organization
    {
        if (! $query instanceof GetOrganizationByIdQuery) {
            throw new \InvalidArgumentException(
                'GetOrganizationByIdHandler received an invalid query.',
            );
        }

        return $this->organizations->findById(
            OrganizationId::fromString($query->organizationId),
        );
    }
}
