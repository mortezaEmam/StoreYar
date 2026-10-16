<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Shipping\Application\Commands\CreateShipment\CreateShipmentCommand;
use StoreYar\Modules\Shipping\Application\Commands\DeliverShipment\DeliverShipmentCommand;
use StoreYar\Modules\Shipping\Application\Commands\ShipShipment\ShipShipmentCommand;
use StoreYar\Modules\Shipping\Application\Queries\GetShipmentById\GetShipmentByIdQuery;
use StoreYar\Modules\Shipping\Application\Queries\ListShipmentsByOrganization\ListShipmentsByOrganizationQuery;
use StoreYar\Modules\Shipping\Application\Services\CreateShipmentIdempotently;
use StoreYar\Modules\Shipping\Domain\Exceptions\InvalidShipmentState;
use StoreYar\Modules\Shipping\Domain\Exceptions\OrderNotShippable;
use StoreYar\Modules\Shipping\Presentation\Http\Requests\CreateShipmentRequest;
use StoreYar\Modules\Shipping\Presentation\Http\Requests\ShipShipmentRequest;
use StoreYar\Modules\Shipping\Presentation\Http\Resources\ShipmentResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;

final class ShipmentController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
        private CreateShipmentIdempotently $createShipmentIdempotently,
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
            new ListShipmentsByOrganizationQuery(
                organizationId: $this->business->businessId(),
                page: $page,
            ),
        );

        return response()->json([
            'data' => ShipmentResource::collection($result->items)->resolve(),
            'meta' => $result->toMeta(),
        ]);
    }

    public function store(CreateShipmentRequest $request): JsonResponse
    {
        try {
            $key = IdempotencyKey::fromHeader($request->header('Idempotency-Key'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        try {
            $result = $this->createShipmentIdempotently->execute(
                organizationId: $this->business->businessId(),
                orderId: $request->string('order_id')->toString(),
                idempotencyKey: $key->value(),
            );

            return (new ShipmentResource($result['shipment']))
                ->response()
                ->setStatusCode($result['replayed'] ? 200 : 201)
                ->header('Idempotency-Replayed', $result['replayed'] ? 'true' : 'false');
        } catch (IdempotencyConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (OrderNotShippable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


    public function show(string $id): JsonResponse
    {
        $shipment = $this->queries->ask(
            new GetShipmentByIdQuery(
                organizationId: $this->business->businessId(),
                shipmentId: $id,
            ),
        );

        if ($shipment === null) {
            return response()->json(['message' => 'Shipment not found.'], 404);
        }

        return (new ShipmentResource($shipment))->response();
    }

    public function ship(ShipShipmentRequest $request, string $id): JsonResponse
    {
        try {
            $shipment = $this->commands->dispatch(
                new ShipShipmentCommand(
                    organizationId: $this->business->businessId(),
                    shipmentId: $id,
                    trackingNumber: $request->string('tracking_number')->toString(),
                ),
            );

            return (new ShipmentResource($shipment))->response();
        } catch (InvalidShipmentState $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Shipment not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function deliver(string $id): JsonResponse
    {
        try {
            $shipment = $this->commands->dispatch(
                new DeliverShipmentCommand(
                    organizationId: $this->business->businessId(),
                    shipmentId: $id,
                ),
            );

            return (new ShipmentResource($shipment))->response();
        } catch (InvalidShipmentState $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Shipment not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
