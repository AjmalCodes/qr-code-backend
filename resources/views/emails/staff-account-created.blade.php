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
                            <h1 style="margin:0; color:#ffffff; font-size:20px;">{{ $restaurant->name }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin-top:0; color:#111827;">Welcome to the team, {{ $staff->name }}!</h2>

                            <p style="color:#374151; font-size:15px; line-height:1.6;">
                                A staff account has been created for you at <strong>{{ $restaurant->name }}</strong>.
                                Use it to log in to the Waiter Panel and manage live table orders.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb; border-radius:6px; margin:24px 0;">
                                <tr>
                                    <td style="padding:20px;">
                                        <p style="margin:0 0 8px 0; color:#111827; font-size:14px;">
                                            <strong>Your Login Credentials</strong>
                                        </p>
                                        <p style="margin:0 0 4px 0; color:#374151; font-size:14px;">
                                            <strong>Email:</strong> {{ $staff->email }}
                                        </p>
                                        <p style="margin:0; color:#374151; font-size:14px;">
                                            <strong>Temporary Password:</strong> {{ $temporaryPassword }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#374151; font-size:15px; line-height:1.6;">
                                Please log in and change your password as soon as possible.
                            </p>

                            <p style="color:#374151; font-size:15px; line-height:1.6; margin-bottom:0;">
                                Best regards,<br>
                                {{ $restaurant->name }}
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
