<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Application\Commands\PushOfflineEvents;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class PushOfflineEventsCommand implements Command
{
    /**
     * @param list<array{client_event_id: string, event_type: string, payload: array, client_occurred_at?: string}> $events
     */
    public function __construct(
        public string $organizationId,
        public ?string $deviceId,
        public array $events,
    ) {}
}
