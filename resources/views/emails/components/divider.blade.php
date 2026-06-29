{{--
    Component: divider
    Props:
        $theme     — EmailTheme instance (auto-shared)
        $margin    — (optional) vertical margin e.g. '24px 0'
--}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="padding: {{ $margin ?? '24px 0' }};">
            <div style="height: 1px; background-color: {{ $theme::border() }};"></div>
        </td>
    </tr>
</table>
