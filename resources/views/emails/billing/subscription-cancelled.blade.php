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
    Subscription cancelled
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your {{ $plan_name }} subscription has been cancelled.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Plan',          'value' => $plan_name],
        ['label' => 'Cancelled on',  'value' => $cancelled_at],
        ['label' => 'Access ends',   'value' => $access_ends_at ?? 'Immediately'],
    ]
])

<p style="
    margin: 20px 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    You can resubscribe at any time from the billing page.
</p>

@include('emails.components.button', ['url' => $billing_url, 'label' => 'Resubscribe'])

@include('emails.components.note', [
    'text' => 'If you cancelled by mistake or have questions, contact us at <a href="mailto:' . $support_email . '" style="color: ' . $theme::primary() . ';">' . $support_email . '</a>'
])

@endsection
