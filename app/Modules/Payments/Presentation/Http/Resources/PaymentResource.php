<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Payments\Domain\Aggregates\Payment;

/** @mixin Payment */
final class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Payment $payment */
        $payment = $this->resource;

        return [
            'id' => $payment->paymentId()->value(),
            'organization_id' => $payment->organizationId(),
            'order_id' => $payment->orderId(),
            'amount' => $payment->amount(),
            'currency' => $payment->currency(),
            'status' => $payment->status()->value,
            'created_at' => $payment->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $payment->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
