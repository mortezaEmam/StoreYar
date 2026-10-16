<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\RefundPayment;

use Illuminate\Database\DatabaseManager;
use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Modules\Sales\Application\Commands\RevertOrderPayment\RevertOrderPaymentCommand;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class RefundPaymentHandler implements CommandHandler
{
    public function __construct(
        private PaymentRepository $payments,
        private CommandBus $commands,
        private Clock $clock,
        private DatabaseManager $db,
    ) {
    }

    public function handle(Command $command): Payment
    {
        if (! $command instanceof RefundPaymentCommand) {
            throw new \InvalidArgumentException(
                'RefundPaymentHandler received an invalid command.',
            );
        }

        return $this->db->connection()->transaction(function () use ($command): Payment {
            $payment = $this->payments->findById(PaymentId::fromString($command->paymentId));

            if ($payment === null || $payment->organizationId() !== $command->organizationId) {
                throw new \InvalidArgumentException('Payment not found.');
            }

            $payment->refund($this->clock->now(), $command->reason);
            $this->payments->save($payment);

            $this->commands->dispatch(
                new RevertOrderPaymentCommand(
                    organizationId: $command->organizationId,
                    orderId: $payment->orderId(),
                    paymentId: $payment->paymentId()->value(),
                ),
            );

            return $payment;
        });
    }
}
