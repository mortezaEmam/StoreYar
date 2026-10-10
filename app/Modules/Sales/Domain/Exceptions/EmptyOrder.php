<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Exceptions;

final class EmptyOrder extends SalesDomainException
{
    public function __construct()
    {
        parent::__construct('Cannot confirm an order without lines.');
    }
}
