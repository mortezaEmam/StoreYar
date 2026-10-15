<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\CompletePayment;

use Illuminate\Database\DatabaseManager;
use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Domain\Enums\PaymentStatus;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Modules\Sales\Application\Commands\MarkOrderPaid\MarkOrderPaidCommand;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CompletePaymentHandler implements CommandHandler
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
        if (! $command instanceof CompletePaymentCommand) {
            throw new \InvalidArgumentException(
                'CompletePaymentHandler received an invalid command.',
            );
        }

        return $this->db->connection()->transaction(
            function () use ($command): Payment {
                $payment = $this->payments->findById(
                    PaymentId::fromString($command->paymentId),
                );

                if (
                    $payment === null
                    || $payment->organizationId() !== $command->organizationId
                ) {
                    throw new \InvalidArgumentException('Payment not found.');
                }

                /*
                 * Idempotency:
                 * پرداختی که قبلاً موفق شده است نباید مجدداً ذخیره شود
                 * یا فرمان پرداخت سفارش را دوباره اجرا کند.
                 */
                if ($payment->status() === PaymentStatus::SUCCEEDED) {
                    return $payment;
                }

                $payment->markSucceeded($this->clock->now());

                $this->payments->save($payment);

                $this->commands->dispatch(
                    new MarkOrderPaidCommand(
                        organizationId: $command->organizationId,
                        orderId: $payment->orderId(),
                        paymentId: $payment->paymentId()->value(),
                    ),
                );

                return $payment;
            },
        );
    }
}
