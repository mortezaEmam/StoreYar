<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\AssignProductCategory;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Catalog\Domain\Contracts\CategoryRepository;
use StoreYar\Modules\Catalog\Domain\ValueObjects\CategoryId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

/**
 * Assigns category_id on catalog_products (tenant-scoped).
 * Uses optimistic bump of version column.
 */
final class AssignProductCategoryHandler implements CommandHandler
{
    public function __construct(private CategoryRepository $categories) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof AssignProductCategoryCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $categoryId = $command->categoryId;
        if ($categoryId !== null && $categoryId !== '') {
            if (! $this->categories->existsInOrganization(
                CategoryId::fromString($categoryId),
                $command->organizationId,
            )) {
                throw new \InvalidArgumentException('Category not found.');
            }
        } else {
            $categoryId = null;
        }

        $updated = DB::table('catalog_products')
            ->where('id', $command->productId)
            ->where('organization_id', $command->organizationId)
            ->update([
                'category_id' => $categoryId,
                'updated_at' => now(),
                'version' => DB::raw('version + 1'),
            ]);

        if ($updated === 0) {
            throw new \InvalidArgumentException('Product not found.');
        }

        return null;
    }
}
