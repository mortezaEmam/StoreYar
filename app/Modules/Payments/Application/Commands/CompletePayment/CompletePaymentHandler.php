<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\CompletePayment;

use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CompletePaymentHandler implements CommandHandler
{
    public function __construct(
        private PaymentRepository $payments,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): Payment
    {
        if (! $command instanceof CompletePaymentCommand) {
            throw new \InvalidArgumentException(
                'CompletePaymentHandler received an invalid command.',
            );
        }

        $payment = $this->payments->findById(PaymentId::fromString($command->paymentId));

        if ($payment === null || $payment->organizationId() !== $command->organizationId) {
            throw new \InvalidArgumentException('Payment not found.');
        }

        $payment->markSucceeded($this->clock->now());
        $this->payments->save($payment);

        return $payment;
    }
}
