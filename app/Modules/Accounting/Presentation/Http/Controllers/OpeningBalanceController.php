<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Accounting\Application\Commands\OpeningBalance\OpeningBalanceCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class OpeningBalanceController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'as_of_date' => ['required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'string', 'size:26'],
            'lines.*.debit_amount' => ['sometimes', 'integer', 'min:0'],
            'lines.*.credit_amount' => ['sometimes', 'integer', 'min:0'],
        ]);

        $result = $this->commands->dispatch(
            new OpeningBalanceCommand(
                organizationId: $this->business->businessId(),
                asOfDate: $data['as_of_date'],
                lines: $data['lines'],
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json(['data' => $result], 201);
    }
}
