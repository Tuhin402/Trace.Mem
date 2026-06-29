@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', ['type' => 'danger', 'message' => 'Action required: your recent payment did not go through.'])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Payment failed
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, we were unable to process your payment for the {{ $plan_name }} plan. Your subscription has been placed on hold.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Plan',        'value' => $plan_name],
        ['label' => 'Amount',      'value' => $amount],
        ['label' => 'Failed on',   'value' => $failed_at],
        ['label' => 'Reason',      'value' => $error_description ?? 'Payment declined'],
    ]
])

<p style="
    margin: 20px 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Please update your payment method to restore full access.
</p>

@include('emails.components.button', ['url' => $billing_url, 'label' => 'Update Payment Method'])

@include('emails.components.note', [
    'text' => 'Need help? Contact us at <a href="mailto:' . $support_email . '" style="color: ' . $theme::primary() . ';">' . $support_email . '</a>'
])

@endsection
