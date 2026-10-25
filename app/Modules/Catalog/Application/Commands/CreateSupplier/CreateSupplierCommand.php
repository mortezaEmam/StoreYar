<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateSupplier;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateSupplierCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $taxId = null,
        public ?string $economicCode = null,
        public int $creditLimitAmount = 0,
        public string $currency = 'IRR',
        public int $paymentTermsDays = 0,
        public ?string $address = null,
        public ?string $notes = null,
    ) {}
}
