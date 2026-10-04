<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Organization\Application\Commands\CreateOrganization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationAlreadyExists;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class CreateOrganizationHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_persists_organization(): void
    {
        $commands = $this->app->make(CommandBus::class);
        $repository = $this->app->make(OrganizationRepository::class);

        /** @var Organization $organization */
        $organization = $commands->dispatch(
            new CreateOrganizationCommand(
                name: 'StoreYar Shop',
                ownerUserId: '01JOWNERUSER00000000000001',
            ),
        );

        self::assertSame('StoreYar Shop', $organization->name());

        $found = $repository->findById($organization->organizationId());

        self::assertNotNull($found);
        self::assertSame('StoreYar Shop', $found->name());
        self::assertSame('01JOWNERUSER00000000000001', $found->ownerUserId());
        self::assertTrue($found->status()->isActive());
    }

    public function test_it_rejects_duplicate_name(): void
    {
        $commands = $this->app->make(CommandBus::class);

        $commands->dispatch(
            new CreateOrganizationCommand(
                name: 'StoreYar Shop',
                ownerUserId: '01JOWNERUSER00000000000001',
            ),
        );

        $this->expectException(OrganizationAlreadyExists::class);

        $commands->dispatch(
            new CreateOrganizationCommand(
                name: 'StoreYar Shop',
                ownerUserId: '01JOWNERUSER00000000000002',
            ),
        );
    }
}
