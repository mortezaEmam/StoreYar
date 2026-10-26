<?php

declare(strict_types=1);

namespace Tests\Feature\StoreYar;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipCommand;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Inventory\Application\Commands\IssueStock\IssueStockCommand;
use StoreYar\Modules\Inventory\Application\Commands\ReceiveStock\ReceiveStockCommand;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockItemModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Tests\TestCase;

/**
 * Core inventory rules: receive, issue, no oversell, ledger present.
 * Run: php artisan test --filter=RetailCoreFlowTest
 */
final class RetailCoreFlowTest extends TestCase
{
    use RefreshDatabase;

    private CommandBus $commands;
    private string $orgId;
    private string $warehouseId;
    private string $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commands = $this->app->make(CommandBus::class);
        $this->seedMinimalCatalog();
    }

    public function test_receive_stock_increases_quantity_and_writes_ledger(): void
    {
        $result = $this->commands->dispatch(
            new ReceiveStockCommand(
                organizationId: $this->orgId,
                warehouseId: $this->warehouseId,
                productId: $this->productId,
                quantity: 10,
                reason: 'test receive',
            ),
        );

        $this->assertSame(10, (int) $result['quantity_after']);

        $item = StockItemModel::query()
            ->where('organization_id', $this->orgId)
            ->where('warehouse_id', $this->warehouseId)
            ->where('product_id', $this->productId)
            ->first();

        $this->assertNotNull($item);
        $this->assertSame(10, (int) $item->quantity);

        $this->assertDatabaseHas('inventory_stock_movements', [
            'organization_id' => $this->orgId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'direction' => 'IN',
        ]);
    }

    public function test_issue_stock_decreases_quantity(): void
    {
        $this->commands->dispatch(
            new ReceiveStockCommand(
                organizationId: $this->orgId,
                warehouseId: $this->warehouseId,
                productId: $this->productId,
                quantity: 5,
            ),
        );

        $result = $this->commands->dispatch(
            new IssueStockCommand(
                organizationId: $this->orgId,
                warehouseId: $this->warehouseId,
                productId: $this->productId,
                quantity: 2,
                reason: 'test issue',
            ),
        );

        $this->assertSame(3, (int) $result['quantity_after']);
    }

    public function test_cannot_issue_more_than_available(): void
    {
        $this->commands->dispatch(
            new ReceiveStockCommand(
                organizationId: $this->orgId,
                warehouseId: $this->warehouseId,
                productId: $this->productId,
                quantity: 1,
            ),
        );

        $this->expectException(\DomainException::class);

        $this->commands->dispatch(
            new IssueStockCommand(
                organizationId: $this->orgId,
                warehouseId: $this->warehouseId,
                productId: $this->productId,
                quantity: 5,
                reason: 'oversell attempt',
            ),
        );
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'healthy');
    }

    private function seedMinimalCatalog(): void
    {
        $ids = new UlidGenerator();

        /** @var User $user */
        $user = $this->commands->dispatch(
            new CreateUserCommand(email: 'retail-test@example.com', name: 'Retail Tester'),
        );
        $this->commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $org = $this->commands->dispatch(
            new CreateOrganizationCommand(
                name: 'Retail Test Org',
                ownerUserId: (string) $user->id(),
            ),
        );
        $this->orgId = is_object($org) && method_exists($org, 'organizationId')
            ? $org->organizationId()->value()
            : (string) (is_object($org) && method_exists($org, 'id') ? $org->id() : $org);

        // Fallback: read last org from DB if command shape differs
        if ($this->orgId === '' || strlen($this->orgId) < 10) {
            $this->orgId = (string) DB::table('organization_organizations')->value('id');
        }

        try {
            $this->commands->dispatch(
                new GrantMembershipCommand(
                    organizationId: $this->orgId,
                    userId: (string) $user->id(),
                    role: Role::OWNER,
                ),
            );
        } catch (\Throwable) {
            // membership may already exist from CreateOrganization
        }

        $this->warehouseId = $ids->generate();
        $wh = new WarehouseModel();
        $wh->id = $this->warehouseId;
        $wh->organization_id = $this->orgId;
        $wh->code = 'MAIN';
        $wh->name = 'Main Warehouse';
        $wh->is_default = true;
        $wh->is_active = true;
        $wh->save();

        $this->productId = $ids->generate();
        $p = new ProductModel();
        $p->id = $this->productId;
        $p->organization_id = $this->orgId;
        $p->sku = 'TEST-SKU-1';
        $p->name = 'Test Product';
        $p->status = 'active';
        $p->unit_price_amount = 1000;
        $p->cost_price_amount = 500;
        $p->currency = 'IRR';
        $p->is_sellable = true;
        $p->is_stockable = true;
        $p->save();
    }
}
