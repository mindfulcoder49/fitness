<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #4f46e5; padding: 32px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700;">{{ $appName }}</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 8px; color: #1a1a2e; font-size: 24px; font-weight: 700;">{{ $group->name }} has launched!</h2>
                            <p style="margin: 0 0 24px; color: #6b7280; font-size: 15px; line-height: 1.6;">Your group has reached its minimum member threshold and is now fully active.</p>

                            @if($group->description)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 16px 20px; border-radius: 0 6px 6px 0;">
                                            <p style="margin: 0; color: #374151; font-size: 14px; line-height: 1.6;">{{ $group->description }}</p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <!-- Stats -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td width="50%" style="padding: 16px; background-color: #f9fafb; border-radius: 6px;">
                                        <p style="margin: 0 0 4px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Members</p>
                                        <p style="margin: 0; color: #1a1a2e; font-size: 20px; font-weight: 700;">{{ $memberCount }}</p>
                                    </td>
                                    <td width="8"></td>
                                    <td width="50%" style="padding: 16px; background-color: #f9fafb; border-radius: 6px;">
                                        <p style="margin: 0 0 4px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Status</p>
                                        <p style="margin: 0; color: #059669; font-size: 20px; font-weight: 700;">Active</p>
                                    </td>
                                </tr>
                            </table>

                            @if($currentTask)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #eff6ff; border: 1px solid #bfdbfe; padding: 16px 20px; border-radius: 6px;">
                                            <p style="margin: 0 0 4px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Current Task</p>
                                            <p style="margin: 0 0 4px; color: #1e40af; font-size: 16px; font-weight: 600;">{{ $currentTask->title }}</p>
                                            @if($currentTask->description)
                                                <p style="margin: 0; color: #374151; font-size: 14px; line-height: 1.5;">{{ $currentTask->description }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 0;">
                                        <a href="{{ $groupUrl }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 6px;">Log in &amp; View Group</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="margin: 0; color: #9ca3af; font-size: 13px;">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
