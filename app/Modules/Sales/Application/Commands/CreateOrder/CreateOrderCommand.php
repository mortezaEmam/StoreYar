<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\CreateOrder;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateOrderCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $customerUserId,
    ) {}
}
