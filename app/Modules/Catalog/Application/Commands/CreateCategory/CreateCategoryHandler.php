<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateCategory;

use StoreYar\Modules\Catalog\Domain\Contracts\CategoryRepository;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class CreateCategoryHandler implements CommandHandler
{
    public function __construct(private CategoryRepository $categories) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof CreateCategoryCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $name = trim($command->name);
        if ($name === '') {
            throw new \InvalidArgumentException('Category name is required.');
        }

        return $this->categories->create($command->organizationId, $name);
    }
}
