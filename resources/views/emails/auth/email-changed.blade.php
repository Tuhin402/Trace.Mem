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
    Email address updated
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your Trace.Mem email address was updated on {{ $changed_at }}.
</p>

@include('emails.components.info-block', [
    'rows' => [
        ['label' => 'Previous email', 'value' => $old_email],
        ['label' => 'New email',      'value' => $new_email],
        ['label' => 'Changed at',     'value' => $changed_at],
    ]
])

@include('emails.components.alert', [
    'type'    => 'warning',
    'message' => 'If you didn\'t make this change, contact support immediately at <a href="mailto:' . $support_email . '" style="color: ' . $theme::warning() . ';">' . $support_email . '</a>'
])

@endsection
