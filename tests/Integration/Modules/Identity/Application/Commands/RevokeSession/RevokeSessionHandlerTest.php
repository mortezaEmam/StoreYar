<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Application\Commands\RevokeSession;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionHandler;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use Tests\TestCase;

final class RevokeSessionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_session_in_database(): void
    {
        $sessionId = SessionId::generate();

        $repository = $this->app->make(SessionRepository::class);

        $repository->create(
            sessionId: $sessionId,
            userId: '01JSESSIONUSER000000000001',
            tokenHash: hash('sha256', 'plain-session-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $this->assertDatabaseHas('identity_sessions', [
            'id' => $sessionId->value(),
            'user_id' => '01JSESSIONUSER000000000001',
            'revoked_at' => null,
        ]);

        $handler = $this->app->make(RevokeSessionHandler::class);

        $result = $handler->handle(
            new RevokeSessionCommand(
                sessionId: $sessionId,
            ),
        );

        self::assertNull($result);

        $this->assertDatabaseMissing('identity_sessions', [
            'id' => $sessionId->value(),
            'revoked_at' => null,
        ]);

        $model = \Illuminate\Support\Facades\DB::table('identity_sessions')
            ->where('id', $sessionId->value())
            ->first();

        self::assertNotNull($model);
        self::assertNotNull($model->revoked_at);
    }
}
