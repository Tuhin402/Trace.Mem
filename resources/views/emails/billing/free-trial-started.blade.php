@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', ['type' => 'success', 'message' => 'Your Founding Offer is now active.'])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Your first month of TraceMem is free
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, welcome to the Founding Offer. You have full access to {{ $plan_name }} for your first month — no charge until your trial ends.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Plan',             'value' => $plan_name],
        ['label' => 'Trial ends',       'value' => $trial_end_date],
        ['label' => 'AutoPay begins',   'value' => $next_billing_date],
        ['label' => 'Monthly renewal',  'value' => $next_billing_amount],
    ]
])

<p style="
    margin: 16px 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 13px;
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Your subscription will automatically continue at {{ $next_billing_amount }}/month on {{ $next_billing_date }}. You can manage or cancel your subscription at any time from the billing page.
</p>

@include('emails.components.button', ['url' => $dashboard_url, 'label' => 'Go to Dashboard'])

@include('emails.components.note', [
    'text' => 'Questions about your Founding Offer? Contact us at <a href="mailto:' . $support_email . '" style="color: ' . $theme::primary() . ';">' . $support_email . '</a>'
])

@endsection
