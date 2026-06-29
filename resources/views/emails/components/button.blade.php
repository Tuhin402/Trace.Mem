{{--
    Component: button
    Props:
        $url       — href
        $label     — button text
        $theme     — EmailTheme instance (auto-shared by EmailServiceProvider)
        $full      — (optional) bool, if true renders full-width
--}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" {{ $full ?? false ? 'width="100%"' : '' }}>
    <tr>
        <td align="{{ $full ?? false ? 'center' : 'left' }}" style="padding: 8px 0;">
            <a
                href="{{ $url }}"
                target="_blank"
                style="
                    display: inline-block;
                    background: linear-gradient(135deg, {{ $theme::primaryDark() }} 0%, {{ $theme::primary() }} 100%);
                    color: #FFFFFF;
                    font-family: {{ $theme::fontFamily() }};
                    font-size: {{ $theme::fontSizeBase() }};
                    font-weight: 600;
                    letter-spacing: 0.01em;
                    text-decoration: none;
                    padding: {{ $theme::paddingButton() }};
                    border-radius: {{ $theme::radiusButton() }};
                    border: 1px solid rgba(167,139,250,0.3);
                    mso-padding-alt: 0;
                    text-align: center;
                    cursor: pointer;
                "
            >
                <!--[if mso]><i style="letter-spacing: 28px; mso-font-width: -100%; mso-text-raise: 14pt;">&nbsp;</i><![endif]-->
                {{ $label }}
                <!--[if mso]><i style="letter-spacing: 28px; mso-font-width: -100%;">&nbsp;</i><![endif]-->
            </a>
        </td>
    </tr>
</table>
