<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\PostStockCount;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class PostStockCountCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $stockCountId,
        public ?string $postedBy = null,
    ) {}
}
