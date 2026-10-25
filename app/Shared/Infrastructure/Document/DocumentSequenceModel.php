<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Document;

use Illuminate\Database\Eloquent\Model;

final class DocumentSequenceModel extends Model
{
    protected $table = 'document_sequences';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'branch_id',
        'document_type',
        'fiscal_year',
        'series',
        'prefix',
        'next_number',
        'padding',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'next_number' => 'integer',
            'padding' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
