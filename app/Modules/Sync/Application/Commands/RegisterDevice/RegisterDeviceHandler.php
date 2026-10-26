<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Application\Commands\RegisterDevice;

use StoreYar\Modules\Sync\Infrastructure\Persistence\Eloquent\SyncDeviceModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class RegisterDeviceHandler implements CommandHandler
{
    public function handle(Command $command): SyncDeviceModel
    {
        if (! $command instanceof RegisterDeviceCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $key = trim($command->deviceKey);
        if ($key === '') {
            throw new \InvalidArgumentException('device_key is required.');
        }

        $device = SyncDeviceModel::query()
            ->where('organization_id', $command->organizationId)
            ->where('device_key', $key)
            ->first();

        if ($device === null) {
            $device = new SyncDeviceModel();
            $device->id = (new UlidGenerator())->generate();
            $device->organization_id = $command->organizationId;
            $device->device_key = $key;
            $device->status = 'ACTIVE';
        }

        $device->name = $command->name ?? $device->name;
        $device->platform = $command->platform ?? $device->platform;
        $device->app_version = $command->appVersion ?? $device->app_version;
        $device->last_seen_at = now();
        $device->save();

        return $device;
    }
}
