<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

/**
 * TASK-001: Tenant isolation HTTP matrix.
 *
 * A member of organization B must not read or mutate organization A's
 * commerce resources by swapping X-Business-Id or resource IDs.
 */
final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Login/register routes use throttle:5,1 — this suite creates many users.
        $this->withoutMiddleware([
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        ]);
    }

    /**
     * @return array{
     *     token: string,
     *     organization_id: string,
     *     product_id: string,
     *     order_id: string,
     *     payment_id: string,
     *     shipment_id: string
     * }
     */
    private function seedOrganizationWithCommerce(
        string $email,
        string $orgName,
        string $sku,
    ): array {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: $email,
                name: $orgName.' Owner',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $user->id(),
                password: 'password123',
            ),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->assertOk()->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', [
                'name' => $orgName,
            ])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => $orgName.' Product',
                'sku' => $sku,
                'unit_price_amount' => 1000,
                'currency' => 'IRR',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'init-'.uniqid('', true))
            ->postJson('/api/stock', [
                'product_id' => $productId,
                'quantity' => 20,
            ])
            ->assertCreated();

        $orderId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $productId,
                'quantity' => 1,
            ])
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'confirm-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk();

        $paymentId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'pay-'.uniqid('', true))
            ->postJson('/api/payments', [
                'order_id' => $orderId,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/payments/'.$paymentId.'/succeed')
            ->assertOk();

        $shipmentId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'ship-'.uniqid('', true))
            ->postJson('/api/shipments', [
                'order_id' => $orderId,
            ])
            ->assertCreated()
            ->json('data.id');

        return [
            'token' => $token,
            'organization_id' => $orgId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'shipment_id' => $shipmentId,
        ];
    }

    /**
     * @return array{token: string, organization_id: string}
     */
    private function seedOrganizationOnly(
        string $email,
        string $orgName,
    ): array {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: $email,
                name: $orgName.' Owner',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $user->id(),
                password: 'password123',
            ),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->assertOk()->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', [
                'name' => $orgName,
            ])
            ->assertCreated()
            ->json('data.id');

        return [
            'token' => $token,
            'organization_id' => $orgId,
        ];
    }

    private function assertDenied(int $status): void
    {
        $this->assertContains(
            $status,
            [401, 403, 404],
            'Cross-tenant access must be denied (401/403/404), got '.$status,
        );
    }

    public function test_non_member_cannot_use_foreign_business_header_on_products(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a@example.com',
            'Org A Shop',
            'SKU-A-1',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b@example.com',
            'Org B Shop',
        );

        // B is not a member of A: X-Business-Id=A must be forbidden.
        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/products')
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/products/'.$orgA['product_id'])
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->postJson('/api/products', [
                'name' => 'Hijack',
                'sku' => 'SKU-HIJACK',
                'unit_price_amount' => 1,
                'currency' => 'IRR',
            ])
            ->assertForbidden();
    }

    public function test_member_of_b_cannot_read_org_a_product_by_id(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a2@example.com',
            'Org A2 Shop',
            'SKU-A-2',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b2@example.com',
            'Org B2 Shop',
        );

        // Valid membership for B, but resource belongs to A → not found.
        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/products/'.$orgA['product_id'])
            ->assertNotFound();

        $list = $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/products')
            ->assertOk()
            ->json('data');

        $ids = array_column($list, 'id');
        $this->assertNotContains($orgA['product_id'], $ids);
    }

    public function test_member_of_b_cannot_mutate_org_a_product(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a3@example.com',
            'Org A3 Shop',
            'SKU-A-3',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b3@example.com',
            'Org B3 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->postJson('/api/products/'.$orgA['product_id'].'/activate')
            ->assertNotFound();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->patchJson('/api/products/'.$orgA['product_id'], [
                'name' => 'Stolen Name',
            ])
            ->assertNotFound();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->postJson('/api/products/'.$orgA['product_id'].'/archive')
            ->assertNotFound();
    }

    public function test_non_member_cannot_use_foreign_business_header_on_stock(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a4@example.com',
            'Org A4 Shop',
            'SKU-A-4',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b4@example.com',
            'Org B4 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/stock')
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/stock/'.$orgA['product_id'])
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->withHeader('Idempotency-Key', 'adj-'.uniqid('', true))
            ->postJson('/api/stock/'.$orgA['product_id'].'/adjust', [
                'direction' => 'increase',
                'amount' => 1,
            ])
            ->assertForbidden();
    }

    public function test_member_of_b_cannot_read_or_adjust_org_a_stock(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a5@example.com',
            'Org A5 Shop',
            'SKU-A-5',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b5@example.com',
            'Org B5 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/stock/'.$orgA['product_id'])
            ->assertNotFound();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->withHeader('Idempotency-Key', 'adj-'.uniqid('', true))
            ->postJson('/api/stock/'.$orgA['product_id'].'/adjust', [
                'direction' => 'increase',
                'amount' => 5,
            ])
            ->assertNotFound();

        $list = $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/stock')
            ->assertOk()
            ->json('data');

        foreach ($list as $row) {
            $this->assertNotSame($orgA['product_id'], $row['product_id'] ?? null);
        }
    }

    public function test_non_member_cannot_use_foreign_business_header_on_orders(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a6@example.com',
            'Org A6 Shop',
            'SKU-A-6',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b6@example.com',
            'Org B6 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/orders')
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/orders/'.$orgA['order_id'])
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->withHeader('Idempotency-Key', 'ord-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertForbidden();
    }

    public function test_member_of_b_cannot_read_or_mutate_org_a_order(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a7@example.com',
            'Org A7 Shop',
            'SKU-A-7',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b7@example.com',
            'Org B7 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/orders/'.$orgA['order_id'])
            ->assertNotFound();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->withHeader('Idempotency-Key', 'cancel-'.uniqid('', true))
            ->postJson('/api/orders/'.$orgA['order_id'].'/cancel')
            ->assertNotFound();

        $list = $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/orders')
            ->assertOk()
            ->json('data');

        $ids = array_column($list, 'id');
        $this->assertNotContains($orgA['order_id'], $ids);
    }

    public function test_non_member_cannot_use_foreign_business_header_on_payments(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a8@example.com',
            'Org A8 Shop',
            'SKU-A-8',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b8@example.com',
            'Org B8 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/payments')
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/payments/'.$orgA['payment_id'])
            ->assertForbidden();
    }

    public function test_member_of_b_cannot_read_or_mutate_org_a_payment(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a9@example.com',
            'Org A9 Shop',
            'SKU-A-9',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b9@example.com',
            'Org B9 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/payments/'.$orgA['payment_id'])
            ->assertNotFound();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->postJson('/api/payments/'.$orgA['payment_id'].'/refund')
            ->assertNotFound();

        $list = $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/payments')
            ->assertOk()
            ->json('data');

        $ids = array_column($list, 'id');
        $this->assertNotContains($orgA['payment_id'], $ids);
    }

    public function test_non_member_cannot_use_foreign_business_header_on_shipments(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a10@example.com',
            'Org A10 Shop',
            'SKU-A-10',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b10@example.com',
            'Org B10 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/shipments')
            ->assertForbidden();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/shipments/'.$orgA['shipment_id'])
            ->assertForbidden();
    }

    public function test_member_of_b_cannot_read_or_mutate_org_a_shipment(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a11@example.com',
            'Org A11 Shop',
            'SKU-A-11',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b11@example.com',
            'Org B11 Shop',
        );

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/shipments/'.$orgA['shipment_id'])
            ->assertNotFound();

        $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->postJson('/api/shipments/'.$orgA['shipment_id'].'/ship', [
                'tracking_number' => 'TRACK-CROSS-TENANT',
            ])
            ->assertNotFound();

        $list = $this->withToken($orgB['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/shipments')
            ->assertOk()
            ->json('data');

        $ids = array_column($list, 'id');
        $this->assertNotContains($orgA['shipment_id'], $ids);
    }

    public function test_owner_of_a_cannot_access_org_b_with_foreign_header(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-a12@example.com',
            'Org A12 Shop',
            'SKU-A-12',
        );
        $orgB = $this->seedOrganizationOnly(
            'owner-b12@example.com',
            'Org B12 Shop',
        );

        // Symmetric: A is not a member of B.
        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/products')
            ->assertForbidden();

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/stock')
            ->assertForbidden();

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/orders')
            ->assertForbidden();

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/payments')
            ->assertForbidden();

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgB['organization_id'])
            ->getJson('/api/shipments')
            ->assertForbidden();
    }

    public function test_legitimate_owner_still_can_access_own_resources(): void
    {
        $orgA = $this->seedOrganizationWithCommerce(
            'owner-legit@example.com',
            'Org Legit Shop',
            'SKU-LEGIT',
        );

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/products/'.$orgA['product_id'])
            ->assertOk()
            ->assertJsonPath('data.id', $orgA['product_id']);

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/stock/'.$orgA['product_id'])
            ->assertOk();

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/orders/'.$orgA['order_id'])
            ->assertOk()
            ->assertJsonPath('data.id', $orgA['order_id']);

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/payments/'.$orgA['payment_id'])
            ->assertOk()
            ->assertJsonPath('data.id', $orgA['payment_id']);

        $this->withToken($orgA['token'])
            ->withHeader('X-Business-Id', $orgA['organization_id'])
            ->getJson('/api/shipments/'.$orgA['shipment_id'])
            ->assertOk()
            ->assertJsonPath('data.id', $orgA['shipment_id']);
    }
}
