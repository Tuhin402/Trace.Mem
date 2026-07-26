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
    Operations Console Verification
</h1>
<p style="
    margin: 0 0 28px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hello {{ $user_name ?? 'Administrator' }},<br><br>
    A request was made to access the TraceMem Operations Console. Please use the following One-Time Password (OTP) to verify your identity:
</p>

<div style="
    text-align: center; 
    font-size: 32px; 
    font-weight: bold; 
    letter-spacing: 4px; 
    padding: 20px; 
    background-color: {{ $theme::surfaceElevated() }}; 
    border: 1px solid {{ $theme::border() }}; 
    color: {{ $theme::primary() }};
    border-radius: {{ $theme::radius() }};
    margin-bottom: 28px;
">
    {{ $otp }}
</div>

@include('emails.components.divider')

@include('emails.components.alert', [
    'type'    => 'info',
    'message' => 'This code is valid for 5 minutes. If you did not request this code, please ignore this email. Your account remains secure.'
])

@endsection
