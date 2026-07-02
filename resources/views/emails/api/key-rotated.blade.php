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
    API key rotated
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your API key <strong style="color: {{ $theme::text() }};">{{ $key_name }}</strong> was rotated on {{ $rotated_at }}. The old key has been revoked.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Key name',         'value' => $key_name],
        ['label' => 'New key last 4',   'value' => '...' . $new_key_last4],
        ['label' => 'Environment',      'value' => ucfirst($environment)],
        ['label' => 'Rotated at',       'value' => $rotated_at],
    ]
])

@include('emails.components.alert', [
    'type'    => 'info',
    'message' => 'Update any integrations using the old key immediately. Requests with the old key will be rejected.'
])

@include('emails.components.button', ['url' => $dashboard_url, 'label' => 'Manage API Keys'])

@include('emails.components.alert', [
    'type'    => 'warning',
    'message' => 'If you didn\'t rotate this key, contact support immediately at <a href="mailto:' . $support_email . '" style="color: ' . $theme::warning() . ';">' . $support_email . '</a>'
])

@endsection
