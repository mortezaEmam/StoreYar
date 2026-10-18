<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\ValueObjects\StockItemId;
use StoreYar\Shared\Domain\Contracts\Clock;
use Tests\TestCase;

final class StockMovementLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_writes_stock_movements_for_init_and_reserve(): void
    {
        $repo = $this->app->make(StockItemRepository::class);
        $clock = $this->app->make(Clock::class);
        $now = $clock->now();

        $item = StockItem::create(
            id: StockItemId::generate(),
            organizationId: '01JORG00000000000000000001',
            productId: '01JPROD0000000000000000001',
            quantity: 10,
            now: $now,
        );
        $repo->save($item);

        $this->assertDatabaseHas('inventory_stock_movements', [
            'product_id' => '01JPROD0000000000000000001',
            'movement_type' => 'initialized',
            'quantity_after' => 10,
        ]);

        $reloaded = $repo->findByOrganizationAndProduct(
            '01JORG00000000000000000001',
            '01JPROD0000000000000000001',
        );
        self::assertNotNull($reloaded);
        $reloaded->reserve(3, $clock->now());
        $repo->save($reloaded);

        $this->assertDatabaseHas('inventory_stock_movements', [
            'product_id' => '01JPROD0000000000000000001',
            'movement_type' => 'reserved',
            'quantity_after' => 7,
            'reserved_after' => 3,
        ]);
    }
}
