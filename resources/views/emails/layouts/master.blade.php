<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $subject ?? $appName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* ── Reset ─────────────────────────────────────────────────── */
        * { box-sizing: border-box; }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        a { text-decoration: none; }

        /* ── Base ──────────────────────────────────────────────────── */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: {{ $theme::background() }};
            font-family: {{ $theme::fontFamily() }};
            font-size: {{ $theme::fontSizeBase() }};
            line-height: {{ $theme::lineHeight() }};
            color: {{ $theme::text() }};
            width: 100% !important;
            min-width: 100%;
        }

        /* ── Links ─────────────────────────────────────────────────── */
        a { color: {{ $theme::primary() }}; }
        a:hover { color: {{ $theme::primaryHover() }}; }

        /* ── Responsive ────────────────────────────────────────────── */
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .stack-column { display: block !important; width: 100% !important; }
            .padding-outer { padding: 24px 16px !important; }
            .padding-card { padding: 24px 20px !important; }
            .btn-full { width: 100% !important; text-align: center !important; }
            .font-large { font-size: 20px !important; }
        }
    </style>
</head>
<body>
<div role="article" aria-roledescription="email" aria-label="{{ $subject ?? $appName }}" lang="en">

    {{-- ── Outer wrapper ─────────────────────────────────────────── --}}
    <table
        role="presentation"
        cellspacing="0"
        cellpadding="0"
        border="0"
        width="100%"
        style="background-color: {{ $theme::background() }}; margin: 0; padding: 0;"
    >
        <tr>
            <td align="center" style="padding: 40px 24px;">

                {{-- ── Email container ──────────────────────────── --}}
                <table
                    role="presentation"
                    class="email-container"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    width="{{ $theme::containerWidth() }}"
                    style="max-width: {{ $theme::containerWidth() }}; width: 100%;"
                >

                    {{-- ── HEADER ──────────────────────────────── --}}
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 32px 32px 24px;
                                background-color: {{ $theme::surface() }};
                                border: 1px solid {{ $theme::border() }};
                                border-bottom: none;
                                border-radius: 8px 8px 0 0;
                            "
                        >
                            {{-- Wordmark --}}
                            <a
                                href="{{ $appUrl }}"
                                style="
                                    display: inline-block;
                                    font-family: {{ $theme::fontFamily() }};
                                    font-size: 22px;
                                    font-weight: 700;
                                    letter-spacing: -0.5px;
                                    color: {{ $theme::text() }};
                                    text-decoration: none;
                                "
                            >
                                Trace<span style="color: {{ $theme::primary() }};">.Mem</span>
                            </a>
                        </td>
                    </tr>

                    {{-- ── DIVIDER under header ─────────────────── --}}
                    <tr>
                        <td style="background-color: {{ $theme::surface() }}; border-left: 1px solid {{ $theme::border() }}; border-right: 1px solid {{ $theme::border() }};">
                            <div style="height: 1px; background-color: {{ $theme::border() }};"></div>
                        </td>
                    </tr>

                    {{-- ── CONTENT ──────────────────────────────── --}}
                    <tr>
                        <td
                            class="padding-card"
                            style="
                                padding: {{ $theme::paddingCard() }};
                                background-color: {{ $theme::surface() }};
                                border-left: 1px solid {{ $theme::border() }};
                                border-right: 1px solid {{ $theme::border() }};
                            "
                        >
                            @yield('content')
                        </td>
                    </tr>

                    {{-- ── FOOTER ──────────────────────────────── --}}
                    <tr>
                        <td style="background-color: {{ $theme::surface() }}; border-left: 1px solid {{ $theme::border() }}; border-right: 1px solid {{ $theme::border() }};">
                            <div style="height: 1px; background-color: {{ $theme::border() }};"></div>
                        </td>
                    </tr>
                    <tr>
                        <td
                            align="center"
                            class="padding-outer"
                            style="
                                padding: 24px 32px;
                                background-color: {{ $theme::surface() }};
                                border: 1px solid {{ $theme::border() }};
                                border-top: none;
                                border-radius: 0 0 8px 8px;
                            "
                        >
                            <p style="margin: 0 0 8px; font-size: {{ $theme::fontSizeTiny() }}; color: {{ $theme::textSubtle() }}; font-family: {{ $theme::fontFamily() }};">
                                Memory Infrastructure for AI Agents
                            </p>
                            <p style="margin: 0 0 8px; font-size: {{ $theme::fontSizeTiny() }}; color: {{ $theme::textSubtle() }}; font-family: {{ $theme::fontFamily() }};">
                                <a href="{{ $appUrl }}" style="color: {{ $theme::textMuted() }}; text-decoration: none;">{{ $appUrl }}</a>
                                &nbsp;&middot;&nbsp;
                                <a href="mailto:{{ $supportEmail }}" style="color: {{ $theme::textMuted() }}; text-decoration: none;">{{ $supportEmail }}</a>
                            </p>
                            <p style="margin: 0; font-size: {{ $theme::fontSizeTiny() }}; color: {{ $theme::textSubtle() }}; font-family: {{ $theme::fontFamily() }};">
                                &copy; {{ $currentYear }} Trace.Mem. All rights reserved.
                            </p>
                        </td>
                    </tr>

                    {{-- ── Outer bottom space ──────────────────── --}}
                    <tr>
                        <td style="height: 24px; background-color: {{ $theme::background() }};"></td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
