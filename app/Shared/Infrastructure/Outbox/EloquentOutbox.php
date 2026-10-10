<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Outbox;

use StoreYar\Shared\Domain\Contracts\Outbox;
use StoreYar\Shared\Domain\Events\DomainEvent;
use StoreYar\Shared\Infrastructure\Persistence\Eloquent\OutboxModel;
use Symfony\Component\Uid\Ulid;

final class EloquentOutbox implements Outbox
{
    public function record(DomainEvent $event): void
    {
        $this->recordMany([$event]);
    }

    public function recordMany(array $events): void
    {
        foreach ($events as $event) {
            if (! $event instanceof DomainEvent) {
                throw new \InvalidArgumentException('Outbox only accepts DomainEvent instances.');
            }

            OutboxModel::query()->create([
                'id' => (string) new Ulid(),
                'aggregate_type' => $event->aggregateType(),
                'aggregate_id' => $event->aggregateId(),
                'event_type' => $event::class,
                'payload' => $this->serialize($event),
                'occurred_at' => $event->occurredAt(),
                'created_at' => new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                'processed_at' => null,
                'attempts' => 0,
                'last_error' => null,
            ]);
        }
    }

    private function serialize(DomainEvent $event): array
    {
        $data = [
            'event_id' => $event->eventId(),
            'aggregate_id' => $event->aggregateId(),
            'aggregate_type' => $event->aggregateType(),
            'occurred_at' => $event->occurredAt()->format(\DateTimeInterface::ATOM),
        ];

        foreach (get_class_methods($event) as $method) {
            if (in_array($method, ['eventId', 'aggregateId', 'aggregateType', 'occurredAt', '__construct'], true)) {
                continue;
            }

            if (! str_starts_with($method, '__') && (new \ReflectionMethod($event, $method))->getNumberOfParameters() === 0) {
                $value = $event->{$method}();

                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format(\DateTimeInterface::ATOM);
                }

                $data[$method] = $value;
            }
        }

        return $data;
    }
}
