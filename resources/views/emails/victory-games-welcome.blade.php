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
                        <td style="background-color: #1a1a2e; padding: 28px 40px; text-align: center;">
                            <p style="margin: 0 0 6px; color: #c9a84c; font-size: 11px; letter-spacing: 4px; text-transform: uppercase;">VibeCode Victory Games</p>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700;">{{ $appName }}</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">

                            <h2 style="margin: 0 0 8px; color: #1a1a2e; font-size: 22px; font-weight: 700;">
                                Your profile is live, {{ $victor->display_name }}!
                            </h2>
                            <p style="margin: 0 0 24px; color: #6b7280; font-size: 15px; line-height: 1.6;">
                                Thanks for competing in <strong>{{ $competition->name }}</strong>@if($competition->held_at) on {{ $competition->held_at->format('F j, Y') }}@endif.
                                Your public victor profile is ready &mdash; claim it to add your bio, links, and avatar, and show the world what you built.
                            </p>

                            <!-- Profile preview box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px 24px;">
                                        <p style="margin: 0 0 4px; color: #9ca3af; font-size: 11px; text-transform: uppercase; letter-spacing: 2px;">Your Profile</p>
                                        <p style="margin: 0 0 12px; color: #1a1a2e; font-size: 17px; font-weight: 700;">{{ $victor->display_name }}</p>
                                        <a href="{{ $victorUrl }}" style="color: #4f46e5; font-size: 13px; word-break: break-all;">{{ $victorUrl }}</a>
                                    </td>
                                </tr>
                            </table>

                            <!-- What you can do -->
                            <p style="margin: 0 0 14px; color: #1a1a2e; font-size: 15px; font-weight: 600;">Once you claim your profile, you can:</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr><td style="padding: 5px 0; color: #374151; font-size: 14px; line-height: 1.5;">&#10003;&nbsp; Add your bio and describe what your app does</td></tr>
                                <tr><td style="padding: 5px 0; color: #374151; font-size: 14px; line-height: 1.5;">&#10003;&nbsp; Link your GitHub, website, and social profiles</td></tr>
                                <tr><td style="padding: 5px 0; color: #374151; font-size: 14px; line-height: 1.5;">&#10003;&nbsp; Upload an avatar</td></tr>
                                <tr><td style="padding: 5px 0; color: #374151; font-size: 14px; line-height: 1.5;">&#10003;&nbsp; Download your certificate of participation or achievement</td></tr>
                                <tr><td style="padding: 5px 0; color: #374151; font-size: 14px; line-height: 1.5;">&#10003;&nbsp; Build a public record of your competition history</td></tr>
                            </table>

                            <!-- Keep showcasing -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="background-color: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 8px; padding: 24px;">
                                        <p style="margin: 0 0 6px; color: #4f46e5; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">Keep Showcasing Your Work</p>
                                        <p style="margin: 0 0 16px; color: #1a1a2e; font-size: 15px; font-weight: 700; line-height: 1.4;">
                                            Create an App entry to showcase ongoing AI testing of your project
                                        </p>
                                        <p style="margin: 0 0 16px; color: #374151; font-size: 14px; line-height: 1.6;">
                                            Your competition run is just the start. You can create a public <strong>App</strong> page on VibeCode Victory Games that acts as a living showcase for your project &mdash; complete with all your AI test runs over time.
                                        </p>

                                        <p style="margin: 0 0 10px; color: #1a1a2e; font-size: 13px; font-weight: 600;">Here&rsquo;s how it works:</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 6px 0;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="width: 28px; vertical-align: top; padding-top: 1px;">
                                                                <span style="display: inline-block; width: 20px; height: 20px; background-color: #4f46e5; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: 700; text-align: center; line-height: 20px;">1</span>
                                                            </td>
                                                            <td style="color: #374151; font-size: 14px; line-height: 1.5;">
                                                                <strong>Claim your profile</strong> on VibeCode Victory Games and create an App entry for your project.
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="width: 28px; vertical-align: top; padding-top: 1px;">
                                                                <span style="display: inline-block; width: 20px; height: 20px; background-color: #4f46e5; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: 700; text-align: center; line-height: 20px;">2</span>
                                                            </td>
                                                            <td style="color: #374151; font-size: 14px; line-height: 1.5;">
                                                                <strong>Log in to <a href="{{ $aiuxtesterUrl }}" style="color: #4f46e5;">AIUXTester</a></strong> and run a new AI test session against your app URL whenever you ship updates.
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="width: 28px; vertical-align: top; padding-top: 1px;">
                                                                <span style="display: inline-block; width: 20px; height: 20px; background-color: #4f46e5; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: 700; text-align: center; line-height: 20px;">3</span>
                                                            </td>
                                                            <td style="color: #374151; font-size: 14px; line-height: 1.5;">
                                                                <strong>Export the run</strong> directly from AIUXTester to VibeCode Victory Games with one click. The run &mdash; including screenshots, AI reasoning, and analysis &mdash; will appear on your App page for anyone to review.
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin: 16px 0 0; color: #6b7280; font-size: 13px; line-height: 1.5;">
                                            It&rsquo;s a great way to demonstrate how your app improves over time and build a public track record of your work.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $registerUrl }}" style="display: inline-block; background-color: #1a1a2e; color: #c9a84c; font-size: 16px; font-weight: 700; text-decoration: none; padding: 14px 36px; border-radius: 6px;">
                                            Claim Your Profile
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 10px;">
                                        <p style="margin: 0; color: #9ca3af; font-size: 12px;">This link creates your account and claims your profile automatically.</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Already have an account -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px;">
                                        <p style="margin: 0 0 6px; color: #374151; font-size: 14px; font-weight: 600;">Already have an account?</p>
                                        <p style="margin: 0 0 8px; color: #6b7280; font-size: 13px; line-height: 1.5;">
                                            Log in, then visit your profile page and click <strong>Claim Profile</strong>. Enter your AIUXTester user ID to verify ownership:
                                        </p>
                                        <p style="margin: 0; font-family: monospace; font-size: 13px; color: #1a1a2e; background-color: #e5e7eb; padding: 6px 10px; border-radius: 4px; word-break: break-all;">{{ $victor->external_user_id }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; color: #9ca3af; font-size: 12px; line-height: 1.5;">
                                This email was sent because your app competed in the VibeCode Victory Games. Your profile will remain public regardless of whether you claim it.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="margin: 0 0 4px; color: #9ca3af; font-size: 13px;">VibeCode Victory Games &mdash; AI-judged UX testing competitions</p>
                            <p style="margin: 0; color: #d1d5db; font-size: 12px;">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
