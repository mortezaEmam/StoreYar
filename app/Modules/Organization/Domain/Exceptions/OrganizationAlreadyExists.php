<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\Exceptions;

final class OrganizationAlreadyExists extends OrganizationDomainException
{
    public function __construct(string $name)
    {
        parent::__construct(
            sprintf('An organization with name "%s" already exists.', $name),
        );
    }
}
