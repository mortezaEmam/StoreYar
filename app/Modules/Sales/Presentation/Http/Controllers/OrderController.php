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

final class OrderController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
        private SessionRepository $sessions,
    ) {}

    public function index(): JsonResponse
    {
        $orders = $this->queries->ask(
            new ListOrdersByOrganizationQuery(
                organizationId: $this->business->businessId(),
            ),
        );

        return OrderResource::collection($orders)->response();
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

        /** @var Order $order */
        $order = $this->commands->dispatch(
            new CreateOrderCommand(
                organizationId: $this->business->businessId(),
                customerUserId: $session->userId(),
            ),
        );

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
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
                    unitPriceAmount: $request->integer('unit_price_amount'),
                    currency: $request->input('currency', 'IRR'),
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

    public function confirm(string $id): JsonResponse
    {
        try {
            /** @var Order $order */
            $order = $this->commands->dispatch(
                new ConfirmOrderCommand(
                    orderId: $id,
                    organizationId: $this->business->businessId(),
                ),
            );

            return (new OrderResource($order))->response();
        } catch (EmptyOrder|InvalidOrderState|InsufficientStockForOrder $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Order not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function cancel(string $id): JsonResponse
    {
        try {
            /** @var Order $order */
            $order = $this->commands->dispatch(
                new CancelOrderCommand(
                    orderId: $id,
                    organizationId: $this->business->businessId(),
                ),
            );

            return (new OrderResource($order))->response();
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
