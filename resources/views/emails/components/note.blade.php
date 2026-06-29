{{--
    Component: note
    Small muted footnote text.
    Props:
        $text  — note content (HTML allowed)
        $theme — EmailTheme instance (auto-shared)
--}}
<p style="
    margin: 16px 0 0;
    font-family: {{ $theme::fontFamily() }};
    font-size: {{ $theme::fontSizeTiny() }};
    color: {{ $theme::textSubtle() }};
    line-height: {{ $theme::lineHeight() }};
">
    {!! $text !!}
</p>
