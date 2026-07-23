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
    You've been invited!
</h1>
<p style="
    margin: 0 0 28px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    <strong>{{ $inviter_name }}</strong> has invited you to join the <strong>{{ $workspace_name }}</strong> workspace on Trace.Mem as a <strong>{{ $role_label }}</strong>.
</p>

@include('emails.components.button', ['url' => $accept_url, 'label' => 'Accept Invitation'])

@include('emails.components.divider')

@include('emails.components.alert', [
    'type'    => 'info',
    'message' => 'This invitation expires in 7 days. If you do not wish to join this workspace, you can safely ignore this email.'
])

@include('emails.components.note', [
    'text' => 'If the button doesn\'t work, copy and paste this link into your browser:<br><a href="' . $accept_url . '" style="color: ' . $theme::primary() . '; word-break: break-all;">' . $accept_url . '</a>'
])

@endsection
