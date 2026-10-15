<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Sales\Application\Commands\AddOrderLine\AddOrderLineCommand;
use StoreYar\Modules\Sales\Application\Commands\CancelOrder\CancelOrderCommand;
use StoreYar\Modules\Sales\Application\Commands\ConfirmOrder\ConfirmOrderCommand;
use StoreYar\Modules\Sales\Application\Commands\CreateOrder\CreateOrderCommand;
use StoreYar\Modules\Sales\Application\Queries\GetOrderById\GetOrderByIdQuery;
use StoreYar\Modules\Sales\Application\Queries\ListOrdersByOrganization\ListOrdersByOrganizationQuery;
use StoreYar\Modules\Sales\Application\Services\CancelOrderIdempotently;
use StoreYar\Modules\Sales\Application\Services\ConfirmOrderIdempotently;
use StoreYar\Modules\Sales\Application\Services\CreateOrderIdempotently;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Exceptions\DuplicateOrderLine;
use StoreYar\Modules\Sales\Domain\Exceptions\EmptyOrder;
use StoreYar\Modules\Sales\Domain\Exceptions\InsufficientStockForOrder;
use StoreYar\Modules\Sales\Domain\Exceptions\InvalidOrderState;
use StoreYar\Modules\Sales\Domain\Exceptions\ProductNotFoundForOrder;
use StoreYar\Modules\Sales\Presentation\Http\Requests\AddOrderLineRequest;
use StoreYar\Modules\Sales\Presentation\Http\Resources\OrderResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;

final class OrderController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
        private SessionRepository $sessions,
        private ConfirmOrderIdempotently $confirmOrderIdempotently,
        private CreateOrderIdempotently $createOrderIdempotently,
        private CancelOrderIdempotently $cancelOrderIdempotently,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = PageRequest::fromQuery(
            $request->query('page'),
            $request->query('per_page'),
        );

        /** @var PageResult $result */
        $result = $this->queries->ask(
            new ListOrdersByOrganizationQuery(
                organizationId: $this->business->businessId(),
                page: $page,
            ),
        );

        return response()->json([
            'data' => OrderResource::collection($result->items)->resolve(),
            'meta' => $result->toMeta(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->queries->ask(
            new GetOrderByIdQuery(
                orderId: $id,
                organizationId: $this->business->businessId(),
            ),
        );

        if ($order === null) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return (new OrderResource($order))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $session = $this->requireSession($request);

        if ($session === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $key = IdempotencyKey::fromHeader($request->header('Idempotency-Key'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        try {
            $result = $this->createOrderIdempotently->execute(
                organizationId: $this->business->businessId(),
                customerUserId: is_object($session->userId()) && method_exists($session->userId(), 'value')
                    ? $session->userId()->value()
                    : (string) $session->userId(),
                idempotencyKey: $key->value(),
            );

            return (new OrderResource($result['order']))
                ->response()
                ->setStatusCode($result['replayed'] ? 200 : 201)
                ->header('Idempotency-Replayed', $result['replayed'] ? 'true' : 'false');
        } catch (IdempotencyConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
    public function addLine(AddOrderLineRequest $request, string $id): JsonResponse
    {
        try {
            /** @var Order $order */
            $order = $this->commands->dispatch(
                new AddOrderLineCommand(
                    orderId: $id,
                    organizationId: $this->business->businessId(),
                    productId: $request->string('product_id')->toString(),
                    quantity: $request->integer('quantity'),
                ),
            );

            return (new OrderResource($order))->response();
        } catch (ProductNotFoundForOrder $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DuplicateOrderLine|InvalidOrderState $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Order not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey === null || trim($idempotencyKey) === '') {
            return response()->json([
                'message' => 'Idempotency-Key header is required.',
            ], 400);
        }

        if (strlen($idempotencyKey) > 128) {
            return response()->json([
                'message' => 'Idempotency-Key is too long.',
            ], 400);
        }

        try {
            $result = $this->confirmOrderIdempotently->execute(
                organizationId: $this->business->businessId(),
                orderId: $id,
                idempotencyKey: trim($idempotencyKey),
            );

            return (new OrderResource($result['order']))
                ->response()
                ->setStatusCode(200)
                ->header('Idempotency-Replayed', $result['replayed'] ? 'true' : 'false');
        } catch (IdempotencyConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (EmptyOrder|InvalidOrderState|InsufficientStockForOrder $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Order not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $key = IdempotencyKey::fromHeader($request->header('Idempotency-Key'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        try {
            $result = $this->cancelOrderIdempotently->execute(
                organizationId: $this->business->businessId(),
                orderId: $id,
                idempotencyKey: $key->value(),
            );

            return (new OrderResource($result['order']))
                ->response()
                ->header('Idempotency-Replayed', $result['replayed'] ? 'true' : 'false');
        } catch (IdempotencyConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (InvalidOrderState $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Order not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
    private function requireSession(Request $request): ?\StoreYar\Modules\Identity\Domain\Entities\Session
    {
        /** @var SessionId|null $sessionId */
        $sessionId = $request->attributes->get('session_id');

        if (! $sessionId instanceof SessionId) {
            return null;
        }

        return $this->sessions->findById($sessionId);
    }

}
