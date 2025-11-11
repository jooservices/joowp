<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'root@example.com'],
            [
                'name' => 'Root',
                'password' => 'root',
            ]
        );
    }
}

