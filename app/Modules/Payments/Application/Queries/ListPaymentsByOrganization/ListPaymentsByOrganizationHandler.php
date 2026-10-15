<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Queries\ListPaymentsByOrganization;

use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use StoreYar\Shared\Application\Pagination\PageResult;

final class ListPaymentsByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private PaymentRepository $payments,
    ) {
    }

    public function handle(Query $query): PageResult
    {
        if (! $query instanceof ListPaymentsByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListPaymentsByOrganizationHandler received an invalid query.',
            );
        }

        return $this->payments->findByOrganizationIdPaginated(
            $query->organizationId,
            $query->page,
        );
    }
}
