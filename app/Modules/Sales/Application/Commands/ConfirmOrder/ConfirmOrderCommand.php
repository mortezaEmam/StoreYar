<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\ConfirmOrder;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ConfirmOrderCommand implements Command
{
    public function __construct(
        public string $orderId,
        public string $organizationId,
    ) {}
}
