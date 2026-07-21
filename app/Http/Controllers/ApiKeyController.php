<?php

namespace App\Http\Controllers;

use App\Jobs\AggregateUsageStatsJob;
use App\Models\ApiKey;
use App\Services\Auth\ApiKeyService;
use App\Services\Auth\SubscriptionCacheService;
use App\Services\Billing\ApiUsageAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiKeyController extends Controller
{
    public function __construct(
        private readonly ApiUsageAnalyticsService $analytics,
        private readonly SubscriptionCacheService $subscriptionCache,
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
                user:        $request->user(),
                name:        $data['name'],
                environment: $data['environment'],
                workspace:   $request->user()?->currentTeam, // scoped to current workspace
            );
        } catch (HttpException $e) {
            return redirect()
                ->route('api.keys')
                ->with('error', $e->getMessage());
        }

        $user = $request->user();

        // Key created — analytics state changed: invalidate analytics + dispatch job.
        // Entitlements are NOT invalidated — creating a key doesn't change entitlements.
        $this->subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');

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

        $user = $request->user();

        $service->revoke($apiKey);

        // Key revoked — both entitlements and analytics are stale.
        $this->subscriptionCache->forgetEntitlements($user);
        $this->subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');

        return response()->noContent();
    }

    public function rotate(Request $request, ApiKey $apiKey, ApiKeyService $service)
    {
        abort_unless($apiKey->user_id === $request->user()->id, 403);

        $user   = $request->user();
        $result = $service->rotateForUser($user, $apiKey);
        // Note: ApiKeyService::rotateForUser() already calls forgetEntitlements()
        // after revocation. We additionally invalidate analytics here.

        $this->subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');

        return redirect()
            ->route('api.keys')
            ->with([
                'plain_key' => $result['plain_key'],
                'message'   => 'API key rotated. Copy it now, it is shown only once.',
            ]);
    }
}