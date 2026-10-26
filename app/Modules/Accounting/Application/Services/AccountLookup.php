<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Services;

use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\AccountModel;

final class AccountLookup
{
    public function bySubtype(string $organizationId, string $subtype): AccountModel
    {
        $account = AccountModel::query()
            ->where('organization_id', $organizationId)
            ->where('subtype', strtoupper($subtype))
            ->where('is_active', true)
            ->orderBy('code')
            ->first();

        if ($account === null) {
            throw new \DomainException(
                "Accounting account with subtype {$subtype} not found. Run storeyar:seed-chart-of-accounts."
            );
        }

        return $account;
    }
}
