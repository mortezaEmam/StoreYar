<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Document;

use Illuminate\Support\Facades\DB;
use StoreYar\Shared\Domain\Contracts\IdGenerator;
use StoreYar\Shared\Infrastructure\Document\DocumentSequenceModel;

/**
 * Atomic, concurrency-safe document number generator.
 *
 * Usage:
 *   $number = $generator->next(
 *       organizationId: $orgId,
 *       documentType: 'SALES_ORDER',
 *       branchId: $branchId,
 *       fiscalYear: 1404,
 *       series: 'DEFAULT',
 *       prefix: 'SO-',
 *   );
 */
final class DocumentNumberGenerator
{
    public function __construct(
        private readonly IdGenerator $idGenerator,
    ) {
    }

    public function next(
        string $organizationId,
        string $documentType,
        ?string $branchId = null,
        ?int $fiscalYear = null,
        string $series = 'DEFAULT',
        ?string $prefix = null,
        int $padding = 6,
    ): string {
        return DB::transaction(function () use (
            $organizationId,
            $documentType,
            $branchId,
            $fiscalYear,
            $series,
            $prefix,
            $padding,
        ): string {
            $query = DocumentSequenceModel::query()
                ->where('organization_id', $organizationId)
                ->where('document_type', $documentType)
                ->where('series', $series);

            if ($branchId === null) {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $branchId);
            }

            if ($fiscalYear === null) {
                $query->whereNull('fiscal_year');
            } else {
                $query->where('fiscal_year', $fiscalYear);
            }

            /** @var DocumentSequenceModel|null $sequence */
            $sequence = $query->lockForUpdate()->first();

            if ($sequence === null) {
                $sequence = new DocumentSequenceModel();
                $sequence->id = $this->idGenerator->generate();
                $sequence->organization_id = $organizationId;
                $sequence->branch_id = $branchId;
                $sequence->document_type = $documentType;
                $sequence->fiscal_year = $fiscalYear;
                $sequence->series = $series;
                $sequence->prefix = $prefix;
                $sequence->next_number = 1;
                $sequence->padding = $padding;
                $sequence->save();
            }

            $current = (int) $sequence->next_number;
            $sequence->next_number = $current + 1;
            if ($prefix !== null) {
                $sequence->prefix = $prefix;
            }
            $sequence->save();

            $padded = str_pad((string) $current, (int) $sequence->padding, '0', STR_PAD_LEFT);
            $usePrefix = $sequence->prefix ?? $prefix ?? '';

            return $usePrefix . $padded;
        });
    }
}
