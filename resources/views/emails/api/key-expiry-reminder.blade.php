@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', ['type' => 'warning', 'message' => 'Your API key expires in 7 days.'])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    API key expiring soon
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your API key <strong style="color: {{ $theme::text() }};">{{ $key_name }}</strong> expires on {{ $expires_at }}. Rotate or replace it before then to keep your integrations running.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Key name',    'value' => $key_name],
        ['label' => 'Prefix',      'value' => $key_prefix . '...'],
        ['label' => 'Last 4',      'value' => '...' . $key_last4],
        ['label' => 'Environment', 'value' => ucfirst($environment)],
        ['label' => 'Expires on',  'value' => $expires_at],
    ]
])

@include('emails.components.button', ['url' => $dashboard_url, 'label' => 'Rotate API Key'])

@endsection
