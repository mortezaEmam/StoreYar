<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\CreateAccount;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateAccountCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public string $type,
        public ?string $subtype = null,
        public ?string $parentId = null,
        public bool $isSystem = false,
        public ?string $currency = null,
    ) {}
}
