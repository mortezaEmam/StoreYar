<?php

declare(strict_types=1);

namespace Tests\Contract;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Critical HTTP surface documented in openapi/openapi.yaml must stay in sync with routes.
 */
final class OpenApiContractTest extends TestCase
{
    private string $spec;

    protected function setUp(): void
    {
        parent::setUp();

        $path = dirname(__DIR__, 2).'/openapi/openapi.yaml';
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
            ['  /auth/rotate:'],
            ['  /business/ping:'],
            ['  /organizations:'],
            ['  /organizations/{id}/members:'],
            ['  /products:'],
            ['  /products/{id}:'],
            ['  /products/{id}/price:'],
            ['  /products/{id}/activate:'],
            ['  /products/{id}/archive:'],
            ['  /stock:'],
            ['  /stock/{productId}/adjust:'],
            ['  /orders:'],
            ['  /orders/{id}:'],
            ['  /orders/{id}/lines:'],
            ['  /orders/{id}/confirm:'],
            ['  /orders/{id}/cancel:'],
            ['  /payments:'],
            ['  /payments/{id}/succeed:'],
            ['  /shipments:'],
            ['  /shipments/{id}/ship:'],
            ['  /audit-log:'],
        ];
    }

    #[DataProvider('criticalPathFragmentsProvider')]
    public function test_critical_path_is_documented(string $fragment): void
    {
        self::assertStringContainsString(
            $fragment,
            $this->spec,
            "OpenAPI missing path fragment: {$fragment}",
        );
    }

    public function test_product_rename_and_price_methods_documented(): void
    {
        // PATCH under /products/{id} (rename) and /products/{id}/price
        self::assertMatchesRegularExpression(
            '/\/products\/\{id\}:[\s\S]*?patch:/',
            $this->spec,
        );
        self::assertStringContainsString('  /products/{id}/price:', $this->spec);
    }

    public function test_security_components_documented(): void
    {
        self::assertStringContainsString('bearerAuth:', $this->spec);
        self::assertStringContainsString('BusinessId:', $this->spec);
        self::assertStringContainsString('IdempotencyKey:', $this->spec);
        self::assertStringContainsString('X-Business-Id', $this->spec);
    }
}
