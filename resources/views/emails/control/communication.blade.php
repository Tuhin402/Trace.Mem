@extends('emails.layouts.master')

@section('content')

{{-- 
  The email body is pre-escaped by the controller/service (e($body)) to prevent HTML injection, 
  but nl2br is applied to preserve line breaks before being passed to this view. 
  We render it as unescaped ({!! !!}) because nl2br creates <br> tags. 
--}}
<div style="
    margin: 0 0 20px;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeBase() }};
    color: {{ $theme::textMuted() }};
    line-height: {{ $theme::lineHeight() }};
">
    {!! nl2br(e($body)) !!}
</div>

@include('emails.components.note', [
    'text' => 'This email was sent by the Operations team at ' . $appName . '.'
])

@endsection
