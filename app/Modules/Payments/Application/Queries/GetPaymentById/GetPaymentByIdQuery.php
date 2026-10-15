<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Queries\GetPaymentById;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetPaymentByIdQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public string $paymentId,
    ) {
    }
}
