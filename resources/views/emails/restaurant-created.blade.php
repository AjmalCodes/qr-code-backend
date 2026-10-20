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
                            <h2 style="margin-top:0; color:#111827;">Congratulations! 🎉</h2>

                            <p style="color:#374151; font-size:15px; line-height:1.6;">
                                Dear {{ $restaurant->name }},
                            </p>

                            <p style="color:#374151; font-size:15px; line-height:1.6;">
                                We are thrilled to welcome you to our platform! Your restaurant account has been successfully created.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb; border-radius:6px; margin:24px 0;">
                                <tr>
                                    <td style="padding:20px;">
                                        <p style="margin:0 0 8px 0; color:#111827; font-size:14px;">
                                            <strong>Your Login Credentials</strong>
                                        </p>
                                        <p style="margin:0 0 4px 0; color:#374151; font-size:14px;">
                                            <strong>Email:</strong> {{ $restaurant->email }}
                                        </p>
                                        <p style="margin:0; color:#374151; font-size:14px;">
                                            <strong>Temporary Password:</strong> {{ $temporaryPassword }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#374151; font-size:15px; line-height:1.6;">
                                <strong>Next Steps:</strong>
                            </p>
                            <ol style="color:#374151; font-size:15px; line-height:1.8; padding-left:20px;">
                                <li>Log in to your restaurant portal using the credentials above</li>
                                <li>Change your password to something secure</li>
                                <li>Complete your restaurant profile with additional details</li>
                                <li>Start accepting and managing orders</li>
                            </ol>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td style="border-radius:6px; background-color:#3b82f6;">
                                        <a href="{{ config('app.frontend_url') }}/login"
                                           style="display:inline-block; padding:12px 24px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:600;">
                                            Login to Your Portal
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#374151; font-size:15px; line-height:1.6;">
                                If you have any questions or need assistance, please don't hesitate to contact our support team.
                            </p>

                            <p style="color:#374151; font-size:15px; line-height:1.6; margin-bottom:0;">
                                Best regards,<br>
                                {{ config('app.name') }} Team
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
