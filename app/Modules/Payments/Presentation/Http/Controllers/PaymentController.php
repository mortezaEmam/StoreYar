<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Payments\Application\Commands\CompletePayment\CompletePaymentCommand;
use StoreYar\Modules\Payments\Application\Commands\FailPayment\FailPaymentCommand;
use StoreYar\Modules\Payments\Application\Commands\RefundPayment\RefundPaymentCommand;
use StoreYar\Modules\Payments\Application\Queries\GetPaymentById\GetPaymentByIdQuery;
use StoreYar\Modules\Payments\Application\Queries\ListPaymentsByOrganization\ListPaymentsByOrganizationQuery;
use StoreYar\Modules\Payments\Application\Services\InitiatePaymentIdempotently;
use StoreYar\Modules\Payments\Domain\Exceptions\InvalidPaymentState;
use StoreYar\Modules\Payments\Domain\Exceptions\OrderNotPayable;
use StoreYar\Modules\Payments\Presentation\Http\Requests\InitiatePaymentRequest;
use StoreYar\Modules\Payments\Presentation\Http\Resources\PaymentResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;

final class PaymentController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
        private InitiatePaymentIdempotently $initiatePaymentIdempotently,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $page = PageRequest::fromQuery(
            $request->query('page'),
            $request->query('per_page'),
        );

        /** @var PageResult $result */
        $result = $this->queries->ask(
            new ListPaymentsByOrganizationQuery(
                organizationId: $this->business->businessId(),
                page: $page,
            ),
        );

        return response()->json([
            'data' => PaymentResource::collection($result->items)->resolve(),
            'meta' => $result->toMeta(),
        ]);
    }

    public function store(InitiatePaymentRequest $request): JsonResponse
    {
        try {
            $key = IdempotencyKey::fromHeader($request->header('Idempotency-Key'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        try {
            $result = $this->initiatePaymentIdempotently->execute(
                organizationId: $this->business->businessId(),
                orderId: $request->string('order_id')->toString(),
                idempotencyKey: $key->value(),
            );

            return (new PaymentResource($result['payment']))
                ->response()
                ->setStatusCode($result['replayed'] ? 200 : 201)
                ->header('Idempotency-Replayed', $result['replayed'] ? 'true' : 'false');
        } catch (IdempotencyConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (OrderNotPayable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $payment = $this->queries->ask(
            new GetPaymentByIdQuery(
                organizationId: $this->business->businessId(),
                paymentId: $id,
            ),
        );

        if ($payment === null) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        return (new PaymentResource($payment))->response();
    }

    public function succeed(string $id): JsonResponse
    {
        try {
            $payment = $this->commands->dispatch(
                new CompletePaymentCommand(
                    organizationId: $this->business->businessId(),
                    paymentId: $id,
                ),
            );

            return (new PaymentResource($payment))->response();
        } catch (InvalidPaymentState $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Payment not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function fail(Request $request, string $id): JsonResponse
    {
        try {
            $payment = $this->commands->dispatch(
                new FailPaymentCommand(
                    organizationId: $this->business->businessId(),
                    paymentId: $id,
                    reason: $request->string('reason')->toString(),
                ),
            );

            return (new PaymentResource($payment))->response();
        } catch (InvalidPaymentState $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Payment not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }



    public function refund(Request $request, string $id): JsonResponse
    {
        try {
            $payment = $this->commands->dispatch(
                new RefundPaymentCommand(
                    organizationId: $this->business->businessId(),
                    paymentId: $id,
                    reason: $request->string('reason')->toString(),
                ),
            );

            return (new PaymentResource($payment))->response();
        } catch (InvalidPaymentState $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Payment not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
