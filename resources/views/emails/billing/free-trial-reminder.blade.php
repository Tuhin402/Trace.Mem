@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', [
    'type'    => 'warning',
    'message' => "Your Founding Offer ends in {{ $days_remaining }} day{{ $days_remaining === 1 ? '' : 's' }}."
])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Your free month ends in {{ $days_remaining }} {{ $days_remaining === 1 ? 'day' : 'days' }}
</h1>
<p style="
    margin: 0 0 20px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your Founding Offer trial is almost over.
</p>

{{-- Dynamic usage summary --}}
@if($memories_count > 0 || $api_requests_count > 0 || $active_api_keys_count > 0)
<div style="
    background: rgba(116, 26, 180, 0.06);
    border: 1px solid rgba(116, 26, 180, 0.15);
    border-radius: 8px;
    padding: 16px 20px;
    margin: 0 0 24px;
">
    <p style="
        margin: 0 0 12px;
        font-family: {{ $theme::fontFamily() }};
        font-size: 13px;
        font-weight: 600;
        color: {{ $theme::text() }};
    ">During your trial, you've:</p>
    <ul style="
        margin: 0;
        padding-left: 18px;
        font-family: {{ $theme::fontFamily() }};
        font-size: 13px;
        color: {{ $theme::textMuted() }};
        line-height: 1.8;
    ">
        @if($memories_count > 0)
        <li>Stored <strong style="color: {{ $theme::text() }};">{{ number_format($memories_count) }}</strong> {{ $memories_count === 1 ? 'memory' : 'memories' }}</li>
        @endif
        @if($api_requests_count > 0)
        <li>Processed <strong style="color: {{ $theme::text() }};">{{ number_format($api_requests_count) }}</strong> API {{ $api_requests_count === 1 ? 'request' : 'requests' }}</li>
        @endif
        @if($active_api_keys_count > 0)
        <li>Used <strong style="color: {{ $theme::text() }};">{{ $active_api_keys_count }}</strong> API {{ $active_api_keys_count === 1 ? 'key' : 'keys' }}</li>
        @endif
    </ul>
</div>
@endif

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Plan',            'value' => $plan_name],
        ['label' => 'Trial ends',      'value' => $trial_end_date],
        ['label' => 'AutoPay begins',  'value' => $trial_end_date],
        ['label' => 'Monthly renewal', 'value' => $next_billing_amount],
    ]
])

<p style="
    margin: 16px 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 13px;
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Your subscription will continue automatically at {{ $next_billing_amount }}/month on {{ $trial_end_date }}. No action is required. You can manage your subscription from the billing page.
</p>

@include('emails.components.button', ['url' => $billing_url, 'label' => 'Manage Subscription'])

@include('emails.components.note', [
    'text' => 'Questions? Contact us at <a href="mailto:' . $support_email . '" style="color: ' . $theme::primary() . ';">' . $support_email . '</a>'
])

@endsection
