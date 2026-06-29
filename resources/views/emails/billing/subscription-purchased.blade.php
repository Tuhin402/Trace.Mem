@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', ['type' => 'success', 'message' => 'Your subscription is now active.'])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Welcome to {{ $plan_name }}
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your Trace.Mem subscription is live. Your AI agents now have access to persistent, intelligent memory.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Plan',           'value' => $plan_name],
        ['label' => 'Billing cycle',  'value' => ucfirst($billing_cycle)],
        ['label' => 'Amount',         'value' => $amount],
        ['label' => 'Starts',         'value' => $starts_at],
        ['label' => 'Next renewal',   'value' => $renews_at ?? 'To be confirmed'],
    ]
])

@include('emails.components.button', ['url' => $dashboard_url, 'label' => 'Go to Dashboard'])

@include('emails.components.note', [
    'text' => 'Questions about your subscription? Contact us at <a href="mailto:' . $support_email . '" style="color: ' . $theme::primary() . ';">' . $support_email . '</a>'
])

@endsection
