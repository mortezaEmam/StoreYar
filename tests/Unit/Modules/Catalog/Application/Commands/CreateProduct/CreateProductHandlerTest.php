<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Application\Commands\CreateProduct;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductHandler;
use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Domain\Enums\ProductStatus;
use StoreYar\Modules\Catalog\Domain\Exceptions\ProductSkuAlreadyExists;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateProductHandlerTest extends TestCase
{
    public function test_it_creates_product(): void
    {
        $now = new DateTimeImmutable('2026-10-08 12:00:00', new DateTimeZone('UTC'));

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->method('findByOrganizationAndSku')
            ->with('01JORG00000000000000000001', 'SKU-001')
            ->willReturn(null);
        $repository->expects($this->once())->method('save');

        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($now);

        $handler = new CreateProductHandler($repository, $clock);

        $product = $handler->handle(
            new CreateProductCommand(
                organizationId: '01JORG00000000000000000001',
                name: 'Sample Product',
                sku: 'SKU-001',
            ),
        );

        self::assertInstanceOf(Product::class, $product);
        self::assertSame('Sample Product', $product->name());
        self::assertSame('SKU-001', $product->sku());
        self::assertSame(ProductStatus::DRAFT, $product->status());
    }

    public function test_it_rejects_duplicate_sku(): void
    {
        $existing = Product::reconstitute(
            id: ProductId::generate(),
            organizationId: '01JORG00000000000000000001',
            name: 'Existing',
            sku: 'SKU-001',
            status: ProductStatus::DRAFT,
            createdAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            updatedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            version: 0,
        );

        $repository = $this->createMock(ProductRepository::class);
        $repository->method('findByOrganizationAndSku')->willReturn($existing);
        $repository->expects($this->never())->method('save');

        $clock = $this->createMock(Clock::class);

        $handler = new CreateProductHandler($repository, $clock);

        $this->expectException(ProductSkuAlreadyExists::class);

        $handler->handle(
            new CreateProductCommand(
                organizationId: '01JORG00000000000000000001',
                name: 'Another Product',
                sku: 'SKU-001',
            ),
        );
    }
}
