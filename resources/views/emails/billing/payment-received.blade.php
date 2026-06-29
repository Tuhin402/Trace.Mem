@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', ['type' => 'success', 'message' => 'Payment received. Thank you.'])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Payment confirmed
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, we received your payment for the {{ $plan_name }} plan.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Amount',     'value' => $amount],
        ['label' => 'Plan',       'value' => $plan_name],
        ['label' => 'Payment ID', 'value' => $payment_id],
        ['label' => 'Date',       'value' => $paid_at],
    ]
])

@include('emails.components.button', ['url' => $billing_url, 'label' => 'View Billing'])

@endsection
