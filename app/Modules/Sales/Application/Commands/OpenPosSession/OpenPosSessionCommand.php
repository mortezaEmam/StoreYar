<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\OpenPosSession;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class OpenPosSessionCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public string $registerCode = 'POS1',
        public int $openingFloatAmount = 0,
        public string $currency = 'IRR',
        public ?string $branchId = null,
        public ?string $openedBy = null,
        public ?string $notes = null,
    ) {}
}
