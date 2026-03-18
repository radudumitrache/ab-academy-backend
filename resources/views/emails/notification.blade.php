<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                       style="background:#ffffff; border-radius:6px; padding:32px;">
                    <tr>
                        <td style="border-bottom:2px solid #e2e8f0; padding-bottom:16px;">
                            <h2 style="margin:0; color:#1a202c; font-size:18px;">
                                {{ config('app.name') }}
                            </h2>
                            <span style="font-size:13px; color:#718096;">
                                {{ $notificationType }} Notification
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:24px; color:#2d3748; font-size:15px; line-height:1.6;">
                            {{ $notificationMessage }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:32px; font-size:12px; color:#a0aec0; border-top:1px solid #e2e8f0; margin-top:32px;">
                            This is an automated notification from {{ config('app.name') }}.
                            Please do not reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
