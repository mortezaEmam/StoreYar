<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\AssignProductCategory;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class AssignProductCategoryCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public ?string $categoryId,
    ) {}
}
