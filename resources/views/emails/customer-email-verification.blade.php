<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#3b82f6; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px;">{{ config('app.name') }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin-top:0; color:#111827;">Confirm your email address</h2>

                            <p style="color:#374151; font-size:15px; line-height:1.6;">
                                Hi {{ $customer->name }}, please confirm your email address to activate loyalty
                                offers on your account. Enter this verification code in the app, or use the link below:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb; border-radius:6px; margin:24px 0;">
                                <tr>
                                    <td style="padding:20px; text-align:center;">
                                        <p style="margin:0; color:#111827; font-size:20px; letter-spacing:2px; font-family: monospace;">
                                            {{ $token }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#9ca3af; font-size:13px; line-height:1.6;">
                                This code expires in 24 hours. If you did not create an account, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px; background-color:#f9fafb; text-align:center;">
                            <p style="margin:0; color:#9ca3af; font-size:12px;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
