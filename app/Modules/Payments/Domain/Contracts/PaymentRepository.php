<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Contracts;

use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;

interface PaymentRepository
{
    public function findById(PaymentId $id): ?Payment;

    /**
     * @return PageResult<Payment>
     */
    public function findByOrganizationIdPaginated(
        string $organizationId,
        PageRequest $page,
    ): PageResult;

    public function save(Payment $payment): void;
}
