<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oryxpace Task Notification</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f4f4f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 24px 32px;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700;">Oryxpace</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px; color: #18181b; font-size: 16px; font-weight: 600;">Task Notification</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; border-radius: 6px; padding: 16px; margin-bottom: 24px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px; color: #71717a; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Task</p>
                                        <p style="margin: 0; color: #18181b; font-size: 16px; font-weight: 600;">{{ $task->title }}</p>
                                    </td>
                                </tr>
                                @if($task->project)
                                <tr>
                                    <td style="padding-top: 12px;">
                                        <p style="margin: 0 0 4px; color: #71717a; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Project</p>
                                        <p style="margin: 0; color: #18181b; font-size: 14px;">{{ $task->project->name }}</p>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding-top: 12px;">
                                        <p style="margin: 0 0 4px; color: #71717a; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Update</p>
                                        <p style="margin: 0; color: #18181b; font-size: 14px;">{{ $message }}</p>
                                    </td>
                                </tr>
                            </table>
                            <a href="{{ config('app.url') }}/tasks/{{ $task->id }}" style="display: inline-block; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 14px;">View Task</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f4f4f5; padding: 16px 32px; text-align: center;">
                            <p style="margin: 0; color: #71717a; font-size: 12px;">You received this because you're subscribed to task updates. <a href="{{ config('app.url') }}/notifications" style="color: #6366f1;">Manage preferences</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
