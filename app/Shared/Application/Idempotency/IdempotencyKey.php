<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Idempotency;

final readonly class IdempotencyKey
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromHeader(?string $header): self
    {
        if ($header === null || trim($header) === '') {
            throw new \InvalidArgumentException('Idempotency-Key header is required.');
        }

        $value = trim($header);

        if (strlen($value) > 128) {
            throw new \InvalidArgumentException('Idempotency-Key is too long.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * Stable short operation id compatible with fixed-length store columns.
     */
    public static function operationId(string ...$parts): string
    {
        return substr(hash('sha256', implode(':', $parts)), 0, 26);
    }
}
