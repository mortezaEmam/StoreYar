<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Errors;

enum ErrorCategory: string
{
    case DOMAIN = 'domain';
    case VALIDATION = 'validation';
    case AUTHORIZATION = 'authorization';
    case NOT_FOUND = 'not_found';
    case CONFLICT = 'conflict';
    case CONCURRENCY = 'concurrency';
    case INFRASTRUCTURE = 'infrastructure';
    case INTEGRATION = 'integration';
}
