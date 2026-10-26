<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PartyPaymentModel extends Model
{
    protected $table = 'party_payments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'party_type', 'party_id', 'direction',
        'amount', 'currency', 'method', 'document_number', 'reference',
        'status', 'journal_entry_id', 'notes', 'created_by', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
