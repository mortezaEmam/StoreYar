<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateBrand;

use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\BrandModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class CreateBrandHandler implements CommandHandler
{
    public function handle(Command $command): BrandModel
    {
        if (! $command instanceof CreateBrandCommand) {
            throw new \InvalidArgumentException('Invalid command for CreateBrandHandler.');
        }

        $name = trim($command->name);
        if ($name === '') {
            throw new \InvalidArgumentException('Brand name is required.');
        }

        $code = $command->code !== null ? trim($command->code) : null;
        if ($code !== null && $code !== '') {
            if (BrandModel::query()->where('organization_id', $command->organizationId)->where('code', $code)->exists()) {
                throw new \DomainException("Brand code already exists: {$code}");
            }
        }

        $brand = new BrandModel();
        $brand->id = (new UlidGenerator())->generate();
        $brand->organization_id = $command->organizationId;
        $brand->code = $code ?: null;
        $brand->name = $name;
        $brand->is_active = true;
        $brand->save();

        return $brand;
    }
}
