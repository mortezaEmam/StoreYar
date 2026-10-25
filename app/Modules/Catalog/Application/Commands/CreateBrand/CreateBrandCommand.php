<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateBrand;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateBrandCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public ?string $code = null,
    ) {}
}
