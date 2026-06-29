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
    Subscription renewed
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your {{ $plan_name }} subscription has renewed successfully. Access continues uninterrupted.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Plan',           'value' => $plan_name],
        ['label' => 'Amount charged', 'value' => $amount],
        ['label' => 'Renewed on',     'value' => $renewed_at],
        ['label' => 'Next renewal',   'value' => $next_renewal_at],
    ]
])

@include('emails.components.button', ['url' => $billing_url, 'label' => 'View Billing'])

@endsection
