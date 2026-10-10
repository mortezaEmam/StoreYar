<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;

/** @mixin Order */
final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        $total = $order->total();

        $lines = [];
        foreach ($order->lines() as $line) {
            $lines[] = [
                'id' => $line->orderLineId()->value(),
                'product_id' => $line->productId(),
                'quantity' => $line->quantity(),
                'unit_price_amount' => $line->unitPrice()->amount(),
                'currency' => $line->unitPrice()->currency(),
                'line_total_amount' => $line->lineTotal()->amount(),
            ];
        }

        return [
            'id' => $order->orderId()->value(),
            'organization_id' => $order->organizationId(),
            'customer_user_id' => $order->customerUserId(),
            'status' => $order->status()->value,
            'total_amount' => $total->amount(),
            'currency' => $total->currency(),
            'lines' => $lines,
            'created_at' => $order->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $order->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
