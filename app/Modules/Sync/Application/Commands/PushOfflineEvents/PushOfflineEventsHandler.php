<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Application\Commands\PushOfflineEvents;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Sales\Application\Commands\PosQuickSale\PosQuickSaleCommand;
use StoreYar\Modules\Sync\Infrastructure\Persistence\Eloquent\SyncEventModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class PushOfflineEventsHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof PushOfflineEventsCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        if ($command->events === []) {
            return ['results' => []];
        }

        $ids = new UlidGenerator();
        $results = [];

        foreach ($command->events as $event) {
            $clientId = trim((string) ($event['client_event_id'] ?? ''));
            $type = strtoupper(trim((string) ($event['event_type'] ?? '')));
            $payload = $event['payload'] ?? [];

            if ($clientId === '' || $type === '' || ! is_array($payload)) {
                $results[] = [
                    'client_event_id' => $clientId,
                    'status' => 'FAILED',
                    'error' => 'client_event_id, event_type and payload required',
                ];
                continue;
            }

            $existing = SyncEventModel::query()
                ->where('organization_id', $command->organizationId)
                ->where('client_event_id', $clientId)
                ->first();

            if ($existing !== null) {
                $results[] = [
                    'client_event_id' => $clientId,
                    'status' => 'DUPLICATE',
                    'server_event_id' => $existing->id,
                    'result_ref' => $existing->result_ref,
                    'applied_status' => $existing->status,
                ];
                continue;
            }

            try {
                $outcome = DB::transaction(function () use ($command, $clientId, $type, $payload, $event, $ids) {
                    $row = new SyncEventModel();
                    $row->id = $ids->generate();
                    $row->organization_id = $command->organizationId;
                    $row->device_id = $command->deviceId;
                    $row->client_event_id = $clientId;
                    $row->event_type = $type;
                    $row->payload = $payload;
                    $row->status = 'PENDING';
                    $row->client_occurred_at = isset($event['client_occurred_at'])
                        ? new \DateTimeImmutable($event['client_occurred_at'])
                        : null;
                    $row->save();

                    $resultRef = null;

                    if ($type === 'POS_SALE') {
                        $sale = $this->commands->dispatch(
                            new PosQuickSaleCommand(
                                organizationId: $command->organizationId,
                                posSessionId: (string) ($payload['pos_session_id'] ?? ''),
                                lines: $payload['lines'] ?? [],
                                paymentMethod: (string) ($payload['payment_method'] ?? 'CASH'),
                                customerId: $payload['customer_id'] ?? null,
                                discountAmount: (int) ($payload['discount_amount'] ?? 0),
                            ),
                        );
                        $resultRef = $sale['sale']->id;
                    } else {
                        throw new \DomainException("Unsupported event_type: {$type}");
                    }

                    $row->status = 'APPLIED';
                    $row->result_ref = $resultRef;
                    $row->applied_at = now();
                    $row->save();

                    return [
                        'client_event_id' => $clientId,
                        'status' => 'APPLIED',
                        'server_event_id' => $row->id,
                        'result_ref' => $resultRef,
                    ];
                });

                $results[] = $outcome;
            } catch (\Throwable $e) {
                // record failure if row was created in a partial way — best effort
                $failed = SyncEventModel::query()
                    ->where('organization_id', $command->organizationId)
                    ->where('client_event_id', $clientId)
                    ->first();

                if ($failed !== null && $failed->status === 'PENDING') {
                    $failed->status = 'FAILED';
                    $failed->error_message = mb_substr($e->getMessage(), 0, 1000);
                    $failed->save();
                } elseif ($failed === null) {
                    $failed = new SyncEventModel();
                    $failed->id = (string) new Ulid();
                    $failed->organization_id = $command->organizationId;
                    $failed->device_id = $command->deviceId;
                    $failed->client_event_id = $clientId;
                    $failed->event_type = $type;
                    $failed->payload = $payload;
                    $failed->status = 'FAILED';
                    $failed->error_message = mb_substr($e->getMessage(), 0, 1000);
                    $failed->save();
                }

                $results[] = [
                    'client_event_id' => $clientId,
                    'status' => 'FAILED',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return ['results' => $results];
    }
}
