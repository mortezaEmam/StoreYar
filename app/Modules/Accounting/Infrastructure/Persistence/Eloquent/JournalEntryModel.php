<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JournalEntryModel extends Model
{
    protected $table = 'accounting_journal_entries';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'fiscal_year_id', 'document_number', 'status',
        'entry_date', 'source_type', 'source_id', 'memo', 'currency',
        'total_debit', 'total_credit', 'posted_at', 'posted_by', 'created_by', 'version',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'total_debit' => 'integer',
            'total_credit' => 'integer',
            'posted_at' => 'datetime',
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLineModel::class, 'journal_entry_id')->orderBy('line_no');
    }
}
