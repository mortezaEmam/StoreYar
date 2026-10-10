<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Exceptions;

use StoreYar\Modules\Sales\Domain\Enums\OrderStatus;

final class InvalidOrderState extends SalesDomainException
{
    public function __construct(string $action, OrderStatus $status)
    {
        parent::__construct(
            sprintf('Cannot %s order in status "%s".', $action, $status->value),
        );
    }
}
