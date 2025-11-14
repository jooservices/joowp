<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Models\LmStudioRole;

final class LmStudioRoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = LmStudioRole::query()
            ->orderByDesc('created_at')
            ->get(['id', 'uuid', 'role_name', 'role_prompt']);

        return ApiResponse::success(
            code: 'lmstudio.roles.list',
            message: 'Roles retrieved.',
            data: [
                'roles' => $roles,
            ],
        );
    }
}
