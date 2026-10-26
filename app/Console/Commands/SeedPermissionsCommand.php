<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent\PermissionModel;
use StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent\RolePermissionModel;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

/**
 * php artisan storeyar:seed-permissions
 */
final class SeedPermissionsCommand extends Command
{
    protected $signature = 'storeyar:seed-permissions';

    protected $description = 'Seed permission catalog and default role-permission matrix';

    /** @var list<array{code: string, name: string, module: string, description: string}> */
    private array $permissions = [
        ['code' => 'catalog.manage', 'name' => 'Manage catalog', 'module' => 'catalog', 'description' => 'Create/update products, brands, units, barcodes'],
        ['code' => 'catalog.view', 'name' => 'View catalog', 'module' => 'catalog', 'description' => 'Read products'],
        ['code' => 'inventory.receive', 'name' => 'Receive stock', 'module' => 'inventory', 'description' => 'Stock receipts'],
        ['code' => 'inventory.issue', 'name' => 'Issue stock', 'module' => 'inventory', 'description' => 'Stock issues'],
        ['code' => 'inventory.view', 'name' => 'View inventory', 'module' => 'inventory', 'description' => 'Stock levels and movements'],
        ['code' => 'inventory.warehouse', 'name' => 'Manage warehouses', 'module' => 'inventory', 'description' => 'Create warehouses'],
        ['code' => 'pos.session', 'name' => 'POS sessions', 'module' => 'pos', 'description' => 'Open/close POS sessions'],
        ['code' => 'pos.sale', 'name' => 'POS sale', 'module' => 'pos', 'description' => 'Perform POS sales'],
        ['code' => 'purchasing.manage', 'name' => 'Manage purchasing', 'module' => 'purchasing', 'description' => 'PO create and receive'],
        ['code' => 'purchasing.view', 'name' => 'View purchasing', 'module' => 'purchasing', 'description' => 'View POs'],
        ['code' => 'accounting.manage', 'name' => 'Manage accounting', 'module' => 'accounting', 'description' => 'Journals and COA'],
        ['code' => 'accounting.view', 'name' => 'View accounting', 'module' => 'accounting', 'description' => 'Read journals and accounts'],
        ['code' => 'reports.view', 'name' => 'View reports', 'module' => 'reports', 'description' => 'Operational and financial reports'],
        ['code' => 'customers.manage', 'name' => 'Manage customers', 'module' => 'crm', 'description' => 'Create customers'],
        ['code' => 'suppliers.manage', 'name' => 'Manage suppliers', 'module' => 'crm', 'description' => 'Create suppliers'],
        ['code' => 'admin.members', 'name' => 'Manage members', 'module' => 'admin', 'description' => 'Grant roles'],
        ['code' => 'admin.audit', 'name' => 'View audit log', 'module' => 'admin', 'description' => 'Read audit trail'],
    ];

    /** @var array<string, list<string>> */
    private array $matrix = [
        'member' => [
            'catalog.view', 'inventory.view', 'reports.view',
        ],
        'cashier' => [
            'catalog.view', 'pos.session', 'pos.sale', 'customers.manage', 'inventory.view',
        ],
        'warehouse' => [
            'catalog.view', 'inventory.view', 'inventory.receive', 'inventory.issue',
            'inventory.warehouse', 'purchasing.view', 'purchasing.manage',
        ],
        'accountant' => [
            'accounting.view', 'accounting.manage', 'reports.view', 'admin.audit',
            'purchasing.view', 'inventory.view',
        ],
        // owner/admin = all via isPrivileged()
    ];

    public function handle(): int
    {
        $ids = new UlidGenerator();
        $permCount = 0;

        foreach ($this->permissions as $p) {
            $exists = PermissionModel::query()->where('code', $p['code'])->exists();
            if ($exists) {
                continue;
            }
            $row = new PermissionModel();
            $row->id = $ids->generate();
            $row->code = $p['code'];
            $row->name = $p['name'];
            $row->module = $p['module'];
            $row->description = $p['description'];
            $row->save();
            $permCount++;
        }

        $rpCount = 0;
        foreach ($this->matrix as $role => $codes) {
            foreach ($codes as $code) {
                $exists = RolePermissionModel::query()
                    ->where('role', $role)
                    ->where('permission_code', $code)
                    ->exists();
                if ($exists) {
                    continue;
                }
                $rp = new RolePermissionModel();
                $rp->id = $ids->generate();
                $rp->role = $role;
                $rp->permission_code = $code;
                $rp->save();
                $rpCount++;
            }
        }

        $this->info("Permissions created: {$permCount}, role mappings created: {$rpCount}");

        return self::SUCCESS;
    }
}
