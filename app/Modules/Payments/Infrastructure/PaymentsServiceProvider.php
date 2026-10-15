<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Payments\Application\Commands\CompletePayment\CompletePaymentCommand;
use StoreYar\Modules\Payments\Application\Commands\CompletePayment\CompletePaymentHandler;
use StoreYar\Modules\Payments\Application\Commands\FailPayment\FailPaymentCommand;
use StoreYar\Modules\Payments\Application\Commands\FailPayment\FailPaymentHandler;
use StoreYar\Modules\Payments\Application\Commands\InitiatePayment\InitiatePaymentCommand;
use StoreYar\Modules\Payments\Application\Commands\InitiatePayment\InitiatePaymentHandler;
use StoreYar\Modules\Payments\Application\Queries\GetPaymentById\GetPaymentByIdHandler;
use StoreYar\Modules\Payments\Application\Queries\GetPaymentById\GetPaymentByIdQuery;
use StoreYar\Modules\Payments\Application\Queries\ListPaymentsByOrganization\ListPaymentsByOrganizationHandler;
use StoreYar\Modules\Payments\Application\Queries\ListPaymentsByOrganization\ListPaymentsByOrganizationQuery;
use StoreYar\Modules\Payments\Application\Services\InitiatePaymentIdempotently;
use StoreYar\Modules\Payments\Domain\Contracts\PayableOrderGateway;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Infrastructure\Persistence\Eloquent\EloquentPaymentRepository;
use StoreYar\Modules\Payments\Infrastructure\Sales\SalesPayableOrderGateway;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

final class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PaymentRepository::class,
            EloquentPaymentRepository::class,
        );

        $this->app->singleton(
            PayableOrderGateway::class,
            SalesPayableOrderGateway::class,
        );

        $this->app->singleton(InitiatePaymentIdempotently::class);

        /*
         * Register command handlers immediately.
         */
        $commands = $this->app->make(CommandHandlerRegistry::class);

        $commands->register(
            InitiatePaymentCommand::class,
            InitiatePaymentHandler::class,
        );

        $commands->register(
            CompletePaymentCommand::class,
            CompletePaymentHandler::class,
        );

        $commands->register(
            FailPaymentCommand::class,
            FailPaymentHandler::class,
        );

        /*
         * Register query handlers immediately.
         */
        $queries = $this->app->make(QueryHandlerRegistry::class);

        $queries->register(
            GetPaymentByIdQuery::class,
            GetPaymentByIdHandler::class,
        );

        $queries->register(
            ListPaymentsByOrganizationQuery::class,
            ListPaymentsByOrganizationHandler::class,
        );
    }
}
