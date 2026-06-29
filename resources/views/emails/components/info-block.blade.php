{{--
    Component: info-block
    Renders key-value pairs in a dark elevated card.
    Props:
        $rows  — array of ['label' => '...', 'value' => '...']
        $theme — EmailTheme instance (auto-shared)
--}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    style="
        background-color: {{ $theme::surfaceElevated() }};
        border: 1px solid {{ $theme::border() }};
        border-radius: {{ $theme::radius() }};
        margin: 16px 0;
    "
>
    @foreach($rows as $i => $row)
    <tr>
        <td
            style="
                padding: 12px 20px;
                border-bottom: {{ !$loop->last ? '1px solid ' . $theme::borderSubtle() : 'none' }};
            "
        >
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="
                        font-family: {{ $theme::fontFamily() }};
                        font-size: {{ $theme::fontSizeSmall() }};
                        color: {{ $theme::textMuted() }};
                        width: 40%;
                        vertical-align: top;
                        padding-right: 16px;
                    ">
                        {{ $row['label'] }}
                    </td>
                    <td style="
                        font-family: {{ $theme::fontFamily() }};
                        font-size: {{ $theme::fontSizeSmall() }};
                        color: {{ $theme::text() }};
                        font-weight: 500;
                        vertical-align: top;
                    ">
                        {{ $row['value'] }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endforeach
</table>
