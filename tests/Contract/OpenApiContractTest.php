<?php

declare(strict_types=1);

namespace Tests\Contract;

use PHPUnit\Framework\TestCase;

/**
 * TASK-012: Critical paths present in openapi/openapi.yaml.
 */
final class OpenApiContractTest extends TestCase
{
    private string $spec;

    protected function setUp(): void
    {
        parent::setUp();

        $path = dirname(__DIR__, 2) . '/openapi/openapi.yaml';
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertNotFalse($content);
        $this->spec = $content;
    }

    public function test_openapi_header(): void
    {
        self::assertStringContainsString('openapi: 3.1.0', $this->spec);
        self::assertStringContainsString('title: StoreYar API', $this->spec);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function criticalPathFragmentsProvider(): array
    {
        return [
            ['  /auth/login:'],
            ['  /auth/register:'],
            ['  /auth/logout:'],
            ['  /auth/me:'],
            ['  /organizations:'],
            ['  /products:'],
            ['  /products/{id}:'],
            ['  /stock:'],
            ['  /orders:'],
            ['  /orders/{id}/confirm:'],
            ['  /payments:'],
            ['  /shipments:'],
        ];
    }

    /**
     * @dataProvider criticalPathFragmentsProvider
     */
    public function test_critical_path_is_documented(string $fragment): void
    {
        self::assertStringContainsString(
            $fragment,
            $this->spec,
            "OpenAPI missing path fragment: {$fragment}",
        );
    }

    public function test_security_components_documented(): void
    {
        self::assertStringContainsString('bearerAuth:', $this->spec);
        self::assertStringContainsString('BusinessId:', $this->spec);
        self::assertStringContainsString('IdempotencyKey:', $this->spec);
        self::assertStringContainsString('X-Business-Id', $this->spec);
    }
}
