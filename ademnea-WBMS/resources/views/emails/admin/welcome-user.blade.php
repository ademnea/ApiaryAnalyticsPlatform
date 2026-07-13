<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome to AdEMNEA</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
        .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.10); }
        .header { background:#1B4332; padding:28px 32px; text-align:center; }
        .header h1 { color:#F8C93A; margin:0; font-size:1.4rem; letter-spacing:.04em; }
        .header p { color:#d1fae5; margin:6px 0 0; font-size:.85rem; }
        .body { padding:32px; color:#374151; }
        .body p { margin:0 0 16px; line-height:1.6; }
        .credentials { background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:16px 20px; margin:20px 0; }
        .credentials table { width:100%; border-collapse:collapse; }
        .credentials td { padding:6px 0; font-size:.9rem; }
        .credentials td:first-child { color:#6b7280; font-weight:500; width:120px; }
        .credentials td:last-child { font-weight:600; color:#111827; font-family:monospace; }
        .btn { display:inline-block; background:#2D6A4F; color:#ffffff !important; text-decoration:none; padding:12px 28px; border-radius:6px; font-weight:600; font-size:.95rem; margin:8px 0 20px; }
        .warning { background:#fef3c7; border:1px solid #fcd34d; border-radius:6px; padding:12px 16px; font-size:.85rem; color:#92400e; margin-bottom:16px; }
        .footer { background:#f9fafb; border-top:1px solid #e5e7eb; padding:16px 32px; font-size:.78rem; color:#9ca3af; text-align:center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>🐝 AdEMNEA</h1>
            <p>Beehive Monitoring System</p>
        </div>
        <div class="body">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            <p>An administrator has created an account for you on the
               <strong>AdEMNEA Analytics Platform</strong>.
               Your login credentials are below:</p>

            <div class="credentials">
                <table>
                    <tr>
                        <td>Email:</td>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td>Password:</td>
                        <td>{{ $plainPassword }}</td>
                    </tr>
                </table>
            </div>

            <p style="text-align:center;">
                <a href="{{ $loginUrl }}" class="btn">Sign In to AdEMNEA</a>
            </p>

            <div class="warning">
                ⚠ <strong>Important:</strong> Please change your password immediately after your first login.
                Go to <em>My Profile → Change Password</em> once you are signed in.
            </div>

            <p>If you have any questions, please contact your system administrator.</p>

            <p style="margin-bottom:0;">
                — The AdEMNEA Team
            </p>
        </div>
        <div class="footer">
            AdEMNEA Analytics Platform &copy; {{ date('Y') }} &nbsp;|&nbsp; Funded by Norad · NORHED II Programme<br/>
            This is an automated message — please do not reply.
        </div>
    </div>
</body>
</html>
