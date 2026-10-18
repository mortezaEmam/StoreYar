<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Default seeder intentionally does not create App\Models\User.
 * Use Identity application commands / API register for real users.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Identity users: prefer API or artisan commands over App\Models\User factory.
    }
}
