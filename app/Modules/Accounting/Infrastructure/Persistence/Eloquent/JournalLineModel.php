<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class JournalLineModel extends Model
{
    protected $table = 'accounting_journal_lines';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'journal_entry_id', 'account_id', 'debit_amount', 'credit_amount',
        'currency', 'memo', 'line_no',
    ];

    protected function casts(): array
    {
        return [
            'debit_amount' => 'integer',
            'credit_amount' => 'integer',
            'line_no' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
