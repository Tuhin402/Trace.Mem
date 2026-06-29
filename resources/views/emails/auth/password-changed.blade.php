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
    Password changed
</h1>
<p style="
    margin: 0 0 24px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    Hi {{ $user_name }}, your Trace.Mem password was changed on {{ $changed_at }}.
</p>

@include('emails.components.alert', [
    'type'    => 'warning',
    'message' => 'If you made this change, no action is needed. If you don\'t recognise this activity, secure your account immediately.'
])

@include('emails.components.button', ['url' => $security_url, 'label' => 'Review Security Settings'])

@include('emails.components.note', [
    'text' => 'If you need help, contact us at <a href="mailto:' . $support_email . '" style="color: ' . $theme::primary() . ';">' . $support_email . '</a>'
])

@endsection
