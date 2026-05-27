<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Services\Auth\ApiKeyService;
use App\Services\Billing\ApiUsageAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiKeyController extends Controller
{
    public function __construct(
        private readonly ApiUsageAnalyticsService $analytics,
    ) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $usage = $this->analytics->forUser($user, []);   // all-time base for stats

        return Inertia::render('app/ApiKeys', [
            'apiKeys'    => $user?->apiKeys()->latest()->get() ?? [],
            'plan'       => $user?->currentSubscription?->subscriptionPlan,
            'usageStats' => $usage['summary'],
            'usageLogs'  => $usage['recent'],
            'flash'      => [
                'plain_key' => session('plain_key'),
                'message'   => session('message'),
                'error'     => session('error'),
            ],
        ]);
    }

    public function store(Request $request, ApiKeyService $service)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'environment' => ['required', 'in:test,live'],
        ]);

        try {
            $result = $service->createForUser(
                $request->user(),
                $data['name'],
                $data['environment']
            );
        } catch (HttpException $e) {
            return redirect()
                ->route('api.keys')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('api.keys')
            ->with([
                'plain_key' => $result['plain_key'],
                'message'   => 'API key created. Copy it now — it is shown only once.',
            ]);
    }

    public function destroy(Request $request, ApiKey $apiKey, ApiKeyService $service)
    {
        abort_unless($apiKey->user_id === $request->user()->id, 403);

        $service->revoke($apiKey);

        return response()->noContent();
    }

    public function rotate(Request $request, ApiKey $apiKey, ApiKeyService $service)
    {
        abort_unless($apiKey->user_id === $request->user()->id, 403);
        $result = $service->rotateForUser($request->user(), $apiKey);

        return redirect()
            ->route('api.keys')
            ->with([
                'plain_key' => $result['plain_key'],
                'message'   => 'API key rotated. Copy it now, it is shown only once.',
            ]);
    }
}