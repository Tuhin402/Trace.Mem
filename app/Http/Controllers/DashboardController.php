<?php

namespace App\Http\Controllers;

use App\Models\Memory;
use App\Models\WorkspaceAuditLog;
use App\Models\ApiKey;
use Illuminate\Support\Facades\DB;
use App\Services\Billing\ApiUsageAnalyticsService;
use App\Services\Billing\BillingCatalogService;
use App\Services\Billing\FreeTrialEligibilityService;
use App\Services\Cache\TraceMemCache;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BillingCatalogService $catalog,
        private readonly ApiUsageAnalyticsService $analytics,
        private readonly TraceMemCache $cache,
        private readonly FreeTrialEligibilityService $trialService,
    ) {}

    public function index(Request $request)
    {
        $user    = $request->user();
        $filters = $request->only(['period', 'month']);

        // ── Analytics read-through ─────────────────────────────────────────
        // Filtered requests (period/month params present): skip cache, hit DB directly.
        // Unfiltered requests: use cache. Cache is populated by AggregateUsageStatsJob
        // on data-changing events (key revoke/rotate/create, subscription cancel, webhook).
        //
        // Do NOT dispatch AggregateUsageStatsJob from here — analytics are refreshed
        // by state-change events, not by dashboard loads.
        $hasFilters = ! empty($filters['period']) || ! empty($filters['month']);

        if ($hasFilters) {
            // Filtered: bypass cache and query DB directly
            $usage = $this->analytics->forUser($user, $filters);
        } else {
            // Unfiltered: use cache with 'all_time' as the period key
            $period = 'all_time';
            $usage  = $this->cache->rememberAnalytics(
                $user,
                $period,
                fn () => $this->analytics->forUser($user, [])
            );
        }

        // Today's insights are always fresh (short TTL data, period='today')
        $todayInsights = $this->cache->rememberAnalytics(
            $user,
            'today',
            fn () => $this->analytics->insightsForUser($user, ['period' => 'today'])
        );

        $tenantStats = $this->cache->rememberTenantStats($user, function () use ($user) {
            $workspaces = $user->ownedTeams()->get();
            $workspaceIds = $workspaces->pluck('id');

            // ── Workspaces ──────────────────────────────────────────────────
            $totalWorkspaces = $workspaces->count();
            $activeWorkspaces = $workspaces->where('status', 'active')->count();
            $archivedWorkspaces = $workspaces->where('status', 'archived')->count();

            // ── Members ─────────────────────────────────────────────────────
            $members = DB::table('team_members')
                ->whereIn('team_id', $workspaceIds)
                ->select('user_id', 'role')
                ->get();
            
            $roles = ['owner' => 0, 'admin' => 0, 'developer' => 0, 'member' => 0, 'viewer' => 0];
            $userHighestRole = [];
            $roleLevels = ['owner' => 5, 'admin' => 4, 'developer' => 3, 'member' => 2, 'viewer' => 1];

            foreach ($members as $m) {
                $role = $m->role;
                $level = $roleLevels[$role] ?? 0;
                $uid = $m->user_id;
                if (!isset($userHighestRole[$uid]) || $level > $roleLevels[$userHighestRole[$uid]]) {
                    $userHighestRole[$uid] = $role;
                }
            }
            
            foreach ($userHighestRole as $uid => $role) {
                if (isset($roles[$role])) {
                    $roles[$role]++;
                }
            }

            // ── API Keys ────────────────────────────────────────────────────
            $apiKeys = ApiKey::whereIn('workspace_id', $workspaceIds)->get();
            
            // ── Memories ────────────────────────────────────────────────────
            $totalMemories = Memory::whereIn('workspace_id', $workspaceIds)->count();

            // ── Recent Activity ─────────────────────────────────────────────
            $recentActivity = WorkspaceAuditLog::whereIn('workspace_id', $workspaceIds)
                ->with('actor:id,name,email')
                ->latest('created_at')
                ->take(8)
                ->get();

            return [
                'workspaces' => [
                    'total' => $totalWorkspaces,
                    'active' => $activeWorkspaces,
                    'archived' => $archivedWorkspaces,
                ],
                'members' => [
                    'total' => count($userHighestRole),
                    'roles' => $roles,
                ],
                'apiKeys' => [
                    'total' => $apiKeys->count(),
                    'live' => $apiKeys->where('environment', 'live')->whereNull('revoked_at')->count(),
                    'test' => $apiKeys->where('environment', 'test')->whereNull('revoked_at')->count(),
                ],
                'memories' => [
                    'total' => $totalMemories,
                ],
                'recentActivity' => $recentActivity->toArray(),
            ];
        });

        // Use tenant's owned workspaces for recent memories to align with Tenant ownership
        $ownedWorkspaceIds = $user->ownedTeams()->pluck('teams.id');
        $memories = Memory::whereIn('workspace_id', $ownedWorkspaceIds)
            ->latest('created_at')
            ->take(6)
            ->get();

        // Active subscription (null if cancelled / none)
        $subscription = $user?->currentSubscription;
        $plan         = $subscription?->subscriptionPlan;

        return Inertia::render('app/Dashboard', [
            'plan'            => $plan,
            // Only pass plans if user has no active plan — the UI uses this to show/hide pricing
            'plans'           => $plan ? [] : $this->catalog->activePlans(),
            'apiKeys'         => $user?->apiKeys()->latest()->get() ?? [],
            'usageStats'      => $usage['summary'],
            'usageLogs'       => $usage['recent'],
            'availableMonths' => $usage['months'],
            'todayInsights'   => $todayInsights,
            'tenantStats'     => $tenantStats,
            'memories'        => $memories,
            'selectedFilters' => $filters,
            'subscription'    => $subscription
                ? [
                    'id'            => $subscription->id,
                    'billing_cycle' => $subscription->billing_cycle,
                    'starts_at'     => $subscription->starts_at?->format('M j, Y'),
                    'renews_at'     => $subscription->renews_at?->format('M j, Y'),
                    'ends_at'       => $subscription->ends_at?->format('M j, Y'),
                    'auto_renew'    => $subscription->auto_renew,
                    'is_cancelled'  => $subscription->isCancelled(),
                ]
                : null,
            'founding_offer'  => $this->trialService->getFoundingOfferPresentation($user),
            'flash'           => [
                'message'   => session('message'),
                'plain_key' => session('plain_key'),
                'error'     => session('error'),
            ],
        ]);
    }
}