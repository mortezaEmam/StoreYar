<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use StoreYar\Modules\Authorization\Presentation\Http\Middleware\EnsurePermission;
use StoreYar\Shared\Presentation\Http\Middleware\AssignRequestId;
use Illuminate\Http\Request;
use StoreYar\Modules\Authorization\Presentation\Http\Middleware\EnsureOrganizationMembership;
use StoreYar\Modules\Identity\Presentation\Http\Middleware\AuthenticateSession;
use StoreYar\Modules\Organization\Presentation\Http\Middleware\SetBusinessContext;
use StoreYar\Shared\Presentation\Console\PublishOutboxCommand;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
        $middleware->alias([
            'auth.session' => AuthenticateSession::class,
            'business.context' => SetBusinessContext::class,
            'org.member' => EnsureOrganizationMembership::class,
            'permission' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })

    ->withCommands([
        PublishOutboxCommand::class,
        \App\Console\Commands\SeedDefaultChartOfAccountsCommand::class,
        \App\Console\Commands\SeedPermissionsCommand::class,
    ])
    ->create();
