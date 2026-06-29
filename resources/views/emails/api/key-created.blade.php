@extends('emails.layouts.master')

@section('content')

@include('emails.components.alert', ['type' => 'success', 'message' => 'A new API key has been created on your account.'])

<h1 style="
    margin: 16px 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    API key created
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, a new API key was created for your Trace.Mem account on {{ $created_at }}.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Key name',    'value' => $key_name],
        ['label' => 'Prefix',      'value' => $key_prefix . '...'],
        ['label' => 'Last 4',      'value' => '...' . $key_last4],
        ['label' => 'Environment', 'value' => ucfirst($environment)],
        ['label' => 'Created at',  'value' => $created_at],
    ]
])

@include('emails.components.alert', [
    'type'    => 'warning',
    'message' => 'If you didn\'t create this key, revoke it immediately from the dashboard and contact support.'
])

@include('emails.components.button', ['url' => $dashboard_url, 'label' => 'Manage API Keys'])

@endsection
