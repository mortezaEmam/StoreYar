<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Aggregates;

use DateTimeImmutable;
use StoreYar\Modules\Payments\Domain\Enums\PaymentStatus;
use StoreYar\Modules\Payments\Domain\Events\PaymentFailed;
use StoreYar\Modules\Payments\Domain\Events\PaymentInitiated;
use StoreYar\Modules\Payments\Domain\Events\PaymentSucceeded;
use StoreYar\Modules\Payments\Domain\Exceptions\InvalidPaymentState;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Shared\Domain\Aggregates\AggregateRoot;

final class Payment extends AggregateRoot
{
    private PaymentStatus $status;

    private int $version = 0;

    private function __construct(
        private readonly PaymentId $paymentId,
        private readonly string $organizationId,
        private readonly string $orderId,
        private readonly int $amount,
        private readonly string $currency,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        PaymentStatus $status,
    ) {
        parent::__construct($paymentId->value());
        $this->status = $status;
    }

    public static function initiate(
        PaymentId $id,
        string $organizationId,
        string $orderId,
        int $amount,
        string $currency,
        DateTimeImmutable $now,
    ): self {
        if (trim($organizationId) === '') {
            throw new \InvalidArgumentException('Organization ID is required.');
        }

        if (trim($orderId) === '') {
            throw new \InvalidArgumentException('Order ID is required.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be positive.');
        }

        if (trim($currency) === '') {
            throw new \InvalidArgumentException('Currency is required.');
        }

        $payment = new self(
            paymentId: $id,
            organizationId: $organizationId,
            orderId: $orderId,
            amount: $amount,
            currency: strtoupper($currency),
            createdAt: $now,
            updatedAt: $now,
            status: PaymentStatus::PENDING,
        );

        $payment->recordEvent(
            new PaymentInitiated(
                paymentId: $payment->id(),
                organizationId: $organizationId,
                orderId: $orderId,
                amount: $amount,
                currency: $payment->currency,
                occurredAt: $now,
            ),
        );

        return $payment;
    }

    public static function reconstitute(
        PaymentId $id,
        string $organizationId,
        string $orderId,
        int $amount,
        string $currency,
        PaymentStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        $payment = new self(
            paymentId: $id,
            organizationId: $organizationId,
            orderId: $orderId,
            amount: $amount,
            currency: $currency,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            status: $status,
        );
        $payment->version = $version;

        return $payment;
    }

    public function markSucceeded(DateTimeImmutable $now): void
{
    // تکرار درخواست موفقیت برای پرداخت موفق، بدون اثر است.
    if ($this->status === PaymentStatus::SUCCEEDED) {
        return;
    }

    if (! $this->status->canComplete()) {
        throw new InvalidPaymentState('complete', $this->status);
    }

    $this->status = PaymentStatus::SUCCEEDED;
    $this->updatedAt = $now;
    $this->version++;

    $this->recordEvent(
        new PaymentSucceeded(
            paymentId: $this->id(),
            organizationId: $this->organizationId,
            orderId: $this->orderId,
            occurredAt: $now,
        ),
    );
}

    public function markFailed(DateTimeImmutable $now, string $reason = ''): void
    {
        if (! $this->status->canFail()) {
            throw new InvalidPaymentState('fail', $this->status);
        }

        $this->status = PaymentStatus::FAILED;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new PaymentFailed(
                paymentId: $this->id(),
                organizationId: $this->organizationId,
                orderId: $this->orderId,
                reason: $reason,
                occurredAt: $now,
            ),
        );
    }

    public function paymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }
}
