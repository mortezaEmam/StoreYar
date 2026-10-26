<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\RecordSupplierPayment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RecordSupplierPaymentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $supplierId,
        public int $amount,
        public string $method = 'CASH',
        public string $currency = 'IRR',
        public ?string $reference = null,
        public ?string $notes = null,
        public ?string $createdBy = null,
        public bool $postAccounting = true,
    ) {}
}
