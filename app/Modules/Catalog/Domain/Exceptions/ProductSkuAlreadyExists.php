<?php

// CatalogDomainException.php
namespace StoreYar\Modules\Catalog\Domain\Exceptions;


final class ProductSkuAlreadyExists extends CatalogDomainException
{
    public function __construct(string $sku)
    {
        parent::__construct(
            sprintf('A product with SKU "%s" already exists in this organization.', $sku),
        );
    }
}
