@extends('emails.layouts.master')

@section('content')

{{-- Heading --}}
<h1 style="
    margin: 0 0 8px;
    font-family: {{ $theme::fontFamily() }};
    font-size: 24px;
    font-weight: 700;
    color: {{ $theme::text() }};
    letter-spacing: -0.3px;
    line-height: 1.3;
">
    Confirm your email
</h1>
<p style="
    margin: 0 0 28px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, welcome to Trace.Mem. Tap the button below to verify your email address and activate your account.
</p>

{{-- CTA --}}
@include('emails.components.button', ['url' => $verification_url, 'label' => 'Verify Email Address'])

@include('emails.components.divider')

{{-- Security note --}}
<p style="
    margin: 0;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeSmall() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    This link expires in 60 minutes. If you didn't create an account, you can safely ignore this email.
</p>

{{-- Fallback URL --}}
@include('emails.components.note', [
    'text' => 'If the button doesn\'t work, copy and paste this link into your browser:<br><a href="' . $verification_url . '" style="color: ' . $theme::primary() . '; word-break: break-all;">' . $verification_url . '</a>'
])

@endsection
