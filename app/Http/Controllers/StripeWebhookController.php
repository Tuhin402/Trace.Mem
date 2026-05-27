<?php

namespace App\Http\Controllers;

use App\Models\BillingTransaction;
use App\Models\UserSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = Webhook::constructEvent(
            $request->getContent(),
            $request->header('Stripe-Signature'),
            config('services.stripe.webhook_secret')
        );

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $userId = (int) data_get($session, 'metadata.user_id');
            $planId = (int) data_get($session, 'metadata.plan_id');
            $billingCycle = (string) data_get($session, 'metadata.billing_cycle');

            BillingTransaction::create([
                'user_id' => $userId,
                'subscription_plan_id' => $planId,
                'provider' => 'stripe',
                'provider_checkout_session_id' => $session->id,
                'provider_payment_intent_id' => $session->payment_intent ?? null,
                'provider_subscription_id' => $session->subscription ?? null,
                'billing_cycle' => $billingCycle,
                'currency' => $session->currency ?? 'usd',
                'amount_total' => (int) ($session->amount_total ?? 0),
                'status' => 'paid',
                'raw_payload' => $session,
            ]);

            UserSubscription::updateOrCreate(
                [
                    'user_id' => $userId,
                    'subscription_plan_id' => $planId,
                    'is_active' => true,
                ],
                [
                    'billing_cycle' => $billingCycle,
                    'status' => 'active',
                    'starts_at' => now(),
                    'renews_at' => null,
                    'ends_at' => null,
                    'auto_renew' => true,
                    'overage_enabled' => false,
                    'quotas_snapshot' => [],
                ]
            );

            if ($session->customer && ($user = User::find($userId))) {
                $user->forceFill(['stripe_customer_id' => $session->customer])->save();
            }
        }

        return response()->json(['received' => true]);
    }
}