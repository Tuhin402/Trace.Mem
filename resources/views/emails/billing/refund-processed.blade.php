@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', ['type' => 'info', 'message' => 'Your refund has been processed and is on its way.'])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Refund processed
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your refund has been processed. It typically takes 5 to 10 business days to appear on your statement.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Refund amount', 'value' => $refund_amount],
        ['label' => 'Refund ID',     'value' => $refund_id],
        ['label' => 'Processed on',  'value' => $refunded_at],
    ]
])

@include('emails.components.note', [
    'text' => 'Questions about this refund? Contact us at <a href="mailto:' . $support_email . '" style="color: ' . $theme::primary() . ';">' . $support_email . '</a>'
])

@endsection
