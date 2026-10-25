<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateUnit;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateUnitCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public ?string $symbol = null,
        public bool $isBase = false,
    ) {}
}
