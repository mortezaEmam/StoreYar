<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Application\Commands\RegisterDevice;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RegisterDeviceCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $deviceKey,
        public ?string $name = null,
        public ?string $platform = null,
        public ?string $appVersion = null,
    ) {}
}
