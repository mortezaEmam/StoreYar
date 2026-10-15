<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\InitiatePayment;

use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\Contracts\PayableOrderGateway;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Domain\Exceptions\OrderNotPayable;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class InitiatePaymentHandler implements CommandHandler
{
    public function __construct(
        private PaymentRepository $payments,
        private PayableOrderGateway $orders,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): Payment
    {
        if (! $command instanceof InitiatePaymentCommand) {
            throw new \InvalidArgumentException(
                'InitiatePaymentHandler received an invalid command.',
            );
        }

        $order = $this->orders->findPayable(
            $command->organizationId,
            $command->orderId,
        );

        if ($order === null) {
            throw new OrderNotPayable($command->orderId, 'order not found');
        }

        if ($order->status !== 'confirmed') {
            throw new OrderNotPayable($command->orderId, 'order must be confirmed');
        }

        if ($order->totalAmount <= 0) {
            throw new OrderNotPayable($command->orderId, 'order total must be positive');
        }

        $payment = Payment::initiate(
            id: PaymentId::generate(),
            organizationId: $command->organizationId,
            orderId: $command->orderId,
            amount: $order->totalAmount,
            currency: $order->currency,
            now: $this->clock->now(),
        );

        $this->payments->save($payment);

        return $payment;
    }
}
