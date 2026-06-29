{{--
    Component: alert
    Props:
        $type     — 'info' | 'success' | 'warning' | 'danger'
        $message  — alert text (HTML allowed)
        $theme    — EmailTheme instance (auto-shared)
--}}
@php
    $color = match($type ?? 'info') {
        'success' => $theme::success(),
        'warning' => $theme::warning(),
        'danger'  => $theme::danger(),
        default   => $theme::info(),
    };
    $bg = match($type ?? 'info') {
        'success' => $theme::successBg(),
        'warning' => $theme::warningBg(),
        'danger'  => $theme::dangerBg(),
        default   => $theme::infoBg(),
    };
@endphp
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 16px 0;">
    <tr>
        <td style="
            background-color: {{ $bg }};
            border: 1px solid {{ $color }}33;
            border-left: 3px solid {{ $color }};
            border-radius: {{ $theme::radius() }};
            padding: 14px 18px;
            font-family: {{ $theme::fontFamily() }};
            font-size: {{ $theme::fontSizeSmall() }};
            color: {{ $color }};
            line-height: {{ $theme::lineHeight() }};
        ">
            {!! $message !!}
        </td>
    </tr>
</table>
