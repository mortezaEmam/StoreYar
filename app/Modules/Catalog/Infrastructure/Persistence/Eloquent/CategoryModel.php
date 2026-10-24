<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class CategoryModel extends Model
{
    protected $table = 'catalog_categories';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
    ];
}
