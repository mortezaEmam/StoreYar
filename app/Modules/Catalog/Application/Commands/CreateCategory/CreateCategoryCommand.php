<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateCategory;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateCategoryCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $name,
    ) {}
}
