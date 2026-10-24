<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Tests\TestCase;

final class RequestIdMiddlewareTest extends TestCase
{
    public function test_response_includes_request_id(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'x@y.com',
            'password' => 'password1',
        ]);

        $response->assertHeader('X-Request-Id');
        self::assertNotSame('', $response->headers->get('X-Request-Id'));
    }

    public function test_client_request_id_is_echoed(): void
    {
        $id = '01TESTREQUESTID000000000000';
        $response = $this->withHeader('X-Request-Id', $id)
            ->postJson('/api/auth/login', [
                'email' => 'x@y.com',
                'password' => 'password1',
            ]);

        $response->assertHeader('X-Request-Id', $id);
    }
}
