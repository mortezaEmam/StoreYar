<?php

declare(strict_types=1);

// CannotRemoveLastOwner.php
namespace StoreYar\Modules\Authorization\Domain\Exceptions;

final class CannotRemoveLastOwner extends AuthorizationDomainException
{
    public function __construct()
    {
        parent::__construct('Cannot remove or demote the last owner.');
    }
}
