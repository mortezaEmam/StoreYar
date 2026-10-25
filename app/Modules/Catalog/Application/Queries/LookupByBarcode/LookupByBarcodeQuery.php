<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\LookupByBarcode;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class LookupByBarcodeQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public string $barcode,
    ) {}
}
