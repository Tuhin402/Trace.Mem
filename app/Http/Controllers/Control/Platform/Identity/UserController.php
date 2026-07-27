<?php

namespace App\Http\Controllers\Control\Platform\Identity;

use App\Http\Controllers\Controller;
use App\Services\Control\Platform\Identity\UserQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private UserQueryService $userQueryService
    ) {}

    public function index(Request $request): Response
    {
        $users = $this->userQueryService->getPaginatedList($request);

        return Inertia::render('control/platform/identity/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'tenant']),
        ]);
    }

    public function show(string $uuid): Response
    {
        $user = $this->userQueryService->getProfile($uuid);

        return Inertia::render('control/platform/identity/users/Profile', [
            'user' => $user,
        ]);
    }
}
