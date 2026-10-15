<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Services;

use DateTimeImmutable;
use DateTimeZone;
use StoreYar\Modules\Payments\Application\Commands\InitiatePayment\InitiatePaymentCommand;
use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;
use Symfony\Component\Uid\Ulid;

final class InitiatePaymentIdempotently
{
    private const OPERATION_TYPE = 'payments.initiate';

    private const STATUS_STARTED = 'started';

    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private CommandBus $commands,
        private PaymentRepository $payments,
        private IdempotencyStore $idempotency,
    ) {
    }

    /**
     * @return array{payment: Payment, replayed: bool}
     */
    public function execute(
        string $organizationId,
        string $orderId,
        string $idempotencyKey,
    ): array {
        $operationId = IdempotencyKey::operationId(
            self::OPERATION_TYPE,
            $organizationId,
            $orderId,
            $idempotencyKey,
        );

        $existing = $this->idempotency->find($organizationId, $operationId);

        if ($existing !== null && $existing->status() === self::STATUS_COMPLETED) {
            $savedId = $existing->response()['payment_id'] ?? null;

            if (! is_string($savedId) || $savedId === '') {
                throw new \RuntimeException('Completed idempotency record is missing payment_id.');
            }

            $payment = $this->payments->findById(PaymentId::fromString($savedId));

            if ($payment === null || $payment->organizationId() !== $organizationId) {
                throw new \RuntimeException('Idempotent initiate result references a missing payment.');
            }

            return ['payment' => $payment, 'replayed' => true];
        }

        if ($existing !== null && $existing->status() === self::STATUS_STARTED) {
            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
        }

        $claim = new IdempotencyRecord(
            id: (string) new Ulid(),
            businessId: $organizationId,
            operationId: $operationId,
            operationType: self::OPERATION_TYPE,
            status: self::STATUS_STARTED,
            response: [],
            completedAt: null,
        );

        if (! $this->idempotency->claim($claim)) {
            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
        }

        try {
            /** @var Payment $payment */
            $payment = $this->commands->dispatch(
                new InitiatePaymentCommand(
                    organizationId: $organizationId,
                    orderId: $orderId,
                ),
            );

            $this->idempotency->store(
                new IdempotencyRecord(
                    id: $claim->id(),
                    businessId: $organizationId,
                    operationId: $operationId,
                    operationType: self::OPERATION_TYPE,
                    status: self::STATUS_COMPLETED,
                    response: ['payment_id' => $payment->paymentId()->value()],
                    completedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
                ),
            );

            return ['payment' => $payment, 'replayed' => false];
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
