<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Queries\GetPaymentById;

use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class GetPaymentByIdHandler implements QueryHandler
{
    public function __construct(
        private PaymentRepository $payments,
    ) {
    }

    public function handle(Query $query): ?Payment
    {
        if (! $query instanceof GetPaymentByIdQuery) {
            throw new \InvalidArgumentException(
                'GetPaymentByIdHandler received an invalid query.',
            );
        }

        $payment = $this->payments->findById(PaymentId::fromString($query->paymentId));

        if ($payment === null || $payment->organizationId() !== $query->organizationId) {
            return null;
        }

        return $payment;
    }
}
