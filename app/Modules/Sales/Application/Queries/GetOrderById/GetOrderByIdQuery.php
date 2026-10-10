<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Queries\GetOrderById;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetOrderByIdQuery implements Query
{
    public function __construct(
        public string $orderId,
        public string $organizationId,
    ) {}
}
