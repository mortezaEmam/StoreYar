<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Outbox;

use Illuminate\Support\Facades\DB;
use StoreYar\Shared\Infrastructure\Persistence\Eloquent\OutboxModel;

final class OutboxProcessor
{
    public function __construct(
        private OutboxEventPublisher $publisher,
    ) {}

    /**
     * @return array{processed: int, failed: int}
     */
    public function process(int $limit = 100): array
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be at least 1.');
        }

        $processed = 0;
        $failed = 0;

        $rows = OutboxModel::query()
            ->whereNull('processed_at')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            try {
                DB::transaction(function () use ($row): void {
                    /** @var OutboxModel|null $locked */
                    $locked = OutboxModel::query()
                        ->whereKey($row->getKey())
                        ->whereNull('processed_at')
                        ->lockForUpdate()
                        ->first();

                    if ($locked === null) {
                        return;
                    }

                    $this->publisher->publish(
                        eventType: $locked->event_type,
                        aggregateType: $locked->aggregate_type,
                        aggregateId: $locked->aggregate_id,
                        payload: $locked->payload ?? [],
                    );

                    $locked->processed_at = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
                    $locked->last_error = null;
                    $locked->save();
                });

                $processed++;
            } catch (\Throwable $e) {
                $failed++;

                OutboxModel::query()
                    ->whereKey($row->getKey())
                    ->update([
                        'attempts' => DB::raw('attempts + 1'),
                        'last_error' => mb_substr($e->getMessage(), 0, 2000),
                    ]);
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
        ];
    }
}
