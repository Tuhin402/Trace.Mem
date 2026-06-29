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
    Reset your password
</h1>
<p style="
    margin: 0 0 28px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    We received a request to reset your Trace.Mem password. Tap the button below to choose a new one.
</p>

@include('emails.components.button', ['url' => $reset_url, 'label' => 'Reset Password'])

@include('emails.components.divider')

@include('emails.components.alert', [
    'type'    => 'warning',
    'message' => 'This link expires in 60 minutes. If you didn\'t request a password reset, no action is needed — your password remains unchanged.'
])

@include('emails.components.note', [
    'text' => 'If the button doesn\'t work, copy and paste this link into your browser:<br><a href="' . $reset_url . '" style="color: ' . $theme::primary() . '; word-break: break-all;">' . $reset_url . '</a>'
])

@endsection
