@extends('emails.layouts.master')

@section('content')

<h1 style="
    margin: 0 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Plan expires in 7 days
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your {{ $plan_name }} subscription is set to expire on {{ $expires_at }}. Renew now to keep your AI agents running without interruption.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Plan',          'value' => $plan_name],
        ['label' => 'Billing cycle', 'value' => ucfirst($billing_cycle)],
        ['label' => 'Expires on',    'value' => $expires_at],
    ]
])

@include('emails.components.button', ['url' => $billing_url, 'label' => 'Renew Subscription'])

@include('emails.components.note', [
    'text' => 'If your subscription is set to auto-renew, no action is needed. Questions? <a href="mailto:' . $supportEmail . '" style="color: ' . $theme::primary() . ';">' . $supportEmail . '</a>'
])

@endsection
