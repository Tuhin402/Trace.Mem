<?php

namespace App\Http\Controllers\Control\Billing;

use App\Actions\Control\Billing\GrantFoundingOfferOverrideAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Billing\FreeTrialEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoundingOfferOverrideController extends Controller
{
    /**
     * Grant a manual Founding Offer override to the specified user.
     */
    public function store(
        Request $request,
        User $user,
        GrantFoundingOfferOverrideAction $action,
        FreeTrialEligibilityService $eligibilityService
    ): JsonResponse {
        $validated = $request->validate([
            'reason'  => ['required', 'string', 'min:10', 'max:1000'],
            'consent' => ['required', 'string', 'in:CONSENT'],
        ]);

        $admin = $request->user();

        // The action handles authorization, locking, transaction, idempotency, audit logs, and domain events.
        $action->execute(
            admin: $admin,
            targetUser: $user,
            reason: $validated['reason'],
            ip: $request->ip()
        );

        // Recalculate eligibility immediately to return in response
        $eligibilityDetails = $eligibilityService->getEligibilityDetails($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Founding Offer successfully granted.',
            'eligibility' => $eligibilityDetails,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
