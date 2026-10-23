{{-- E-mail clients need inline styles. $lines are already HTML-escaped by EmailTemplates::render(). --}}
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ setting('site.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#18181b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e4e4e7;">
                <tr>
                    <td style="padding:22px 28px;border-bottom:1px solid #f4f4f5;">
                        <span style="display:inline-block;width:28px;height:28px;line-height:28px;text-align:center;border-radius:8px;background:#FFCF00;color:#111111;font-weight:900;font-size:15px;">b</span>
                        <span style="font-weight:700;font-size:16px;vertical-align:middle;margin-left:8px;">{{ setting('site.name') }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;font-size:15px;line-height:1.6;">
                        @foreach ($lines as $line)
                            <p style="margin:0 0 16px;">{!! nl2br($line) !!}</p>
                        @endforeach

                        @if ($actionUrl)
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr><td style="border-radius:10px;background:#FFCF00;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 22px;font-weight:700;font-size:15px;color:#111111;text-decoration:none;">{{ $actionLabel }}</a>
                                </td></tr>
                            </table>
                            <p style="margin:0;font-size:12px;color:#71717a;word-break:break-all;">{{ __('emails.button_fallback', [], $locale) }}<br><a href="{{ $actionUrl }}" style="color:#3f3f46;">{{ $actionUrl }}</a></p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 28px;background:#fafafa;font-size:12px;color:#71717a;">
                        {{ __('emails.footer', ['site' => setting('site.name')], $locale) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
