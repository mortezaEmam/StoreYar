<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\GetProductById;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetProductByIdQuery implements Query
{
    public function __construct(
        public string $productId,
    ) {}
}
