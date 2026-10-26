<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Accounting\Application\Commands\CreateAccount\CreateAccountCommand;
use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand;
use StoreYar\Modules\Accounting\Application\Commands\PostJournalEntry\PostJournalEntryCommand;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\AccountModel;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\FiscalYearModel;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\JournalEntryModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class AccountingController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function accounts(): JsonResponse
    {
        $items = AccountModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderBy('code')
            ->get();

        return response()->json([
            'data' => $items->map(static fn (AccountModel $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'subtype' => $a->subtype,
                'is_active' => (bool) $a->is_active,
                'is_system' => (bool) $a->is_system,
            ]),
        ]);
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE'],
            'subtype' => ['sometimes', 'nullable', 'string', 'max:32'],
            'parent_id' => ['sometimes', 'nullable', 'string', 'size:26'],
        ]);

        $a = $this->commands->dispatch(
            new CreateAccountCommand(
                organizationId: $this->business->businessId(),
                code: $data['code'],
                name: $data['name'],
                type: $data['type'],
                subtype: $data['subtype'] ?? null,
                parentId: $data['parent_id'] ?? null,
            ),
        );

        return response()->json([
            'data' => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'subtype' => $a->subtype,
            ],
        ], 201);
    }

    public function journals(Request $request): JsonResponse
    {
        $q = JournalEntryModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderByDesc('entry_date')
            ->orderByDesc('created_at')
            ->limit(50);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }

        return response()->json([
            'data' => $q->get()->map(static fn (JournalEntryModel $j) => [
                'id' => $j->id,
                'document_number' => $j->document_number,
                'status' => $j->status,
                'entry_date' => $j->entry_date?->format('Y-m-d'),
                'memo' => $j->memo,
                'total_debit' => $j->total_debit,
                'total_credit' => $j->total_credit,
                'source_type' => $j->source_type,
                'posted_at' => $j->posted_at?->toIso8601String(),
            ]),
        ]);
    }

    public function showJournal(string $id): JsonResponse
    {
        $j = JournalEntryModel::query()
            ->where('id', $id)
            ->where('organization_id', $this->business->businessId())
            ->with('lines')
            ->first();

        if ($j === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $this->journalDetail($j)]);
    }

    public function storeJournal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'memo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'post' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'string', 'size:26'],
            'lines.*.debit_amount' => ['sometimes', 'integer', 'min:0'],
            'lines.*.credit_amount' => ['sometimes', 'integer', 'min:0'],
            'lines.*.memo' => ['sometimes', 'nullable', 'string'],
        ]);

        $j = $this->commands->dispatch(
            new CreateJournalEntryCommand(
                organizationId: $this->business->businessId(),
                entryDate: $data['entry_date'],
                lines: $data['lines'],
                memo: $data['memo'] ?? null,
                currency: $data['currency'] ?? 'IRR',
                post: (bool) ($data['post'] ?? false),
            ),
        );

        return response()->json(['data' => $this->journalDetail($j)], 201);
    }

    public function postJournal(string $id): JsonResponse
    {
        $j = $this->commands->dispatch(
            new PostJournalEntryCommand(
                organizationId: $this->business->businessId(),
                journalEntryId: $id,
            ),
        );

        return response()->json(['data' => $this->journalDetail($j->load('lines'))]);
    }

    public function fiscalYears(): JsonResponse
    {
        $items = FiscalYearModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderByDesc('starts_on')
            ->get();

        return response()->json([
            'data' => $items->map(static fn (FiscalYearModel $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'starts_on' => $f->starts_on?->format('Y-m-d'),
                'ends_on' => $f->ends_on?->format('Y-m-d'),
                'status' => $f->status,
                'is_current' => (bool) $f->is_current,
            ]),
        ]);
    }

    public function storeFiscalYear(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['sometimes', 'boolean'],
        ]);

        $orgId = $this->business->businessId();

        if (! empty($data['is_current'])) {
            FiscalYearModel::query()
                ->where('organization_id', $orgId)
                ->update(['is_current' => false]);
        }

        $f = new FiscalYearModel();
        $f->id = (new UlidGenerator())->generate();
        $f->organization_id = $orgId;
        $f->name = $data['name'];
        $f->starts_on = $data['starts_on'];
        $f->ends_on = $data['ends_on'];
        $f->status = 'OPEN';
        $f->is_current = (bool) ($data['is_current'] ?? false);
        $f->save();

        return response()->json([
            'data' => [
                'id' => $f->id,
                'name' => $f->name,
                'starts_on' => $f->starts_on?->format('Y-m-d'),
                'ends_on' => $f->ends_on?->format('Y-m-d'),
                'is_current' => (bool) $f->is_current,
            ],
        ], 201);
    }

    private function journalDetail(JournalEntryModel $j): array
    {
        $j->loadMissing('lines');

        return [
            'id' => $j->id,
            'document_number' => $j->document_number,
            'status' => $j->status,
            'entry_date' => $j->entry_date?->format('Y-m-d'),
            'memo' => $j->memo,
            'currency' => $j->currency,
            'total_debit' => $j->total_debit,
            'total_credit' => $j->total_credit,
            'source_type' => $j->source_type,
            'source_id' => $j->source_id,
            'posted_at' => $j->posted_at?->toIso8601String(),
            'lines' => $j->lines->map(static fn ($l) => [
                'id' => $l->id,
                'account_id' => $l->account_id,
                'debit_amount' => $l->debit_amount,
                'credit_amount' => $l->credit_amount,
                'memo' => $l->memo,
                'line_no' => $l->line_no,
            ])->values()->all(),
        ];
    }
}
