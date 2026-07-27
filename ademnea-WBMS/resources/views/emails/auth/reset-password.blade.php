<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Your AdEMNEA Password</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
        .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.10); }
        .header { background:#1B4332; padding:28px 32px; text-align:center; }
        .header h1 { color:#F8C93A; margin:0; font-size:1.4rem; letter-spacing:.04em; }
        .header p { color:#d1fae5; margin:6px 0 0; font-size:.85rem; }
        .body { padding:32px; color:#374151; }
        .body p { margin:0 0 16px; line-height:1.6; }
        .btn { display:inline-block; background:#2D6A4F; color:#ffffff !important; text-decoration:none; padding:12px 28px; border-radius:6px; font-weight:600; font-size:.95rem; margin:8px 0 20px; }
        .expiry { background:#fef3c7; border:1px solid #fcd34d; border-radius:6px; padding:12px 16px; font-size:.85rem; color:#92400e; margin-bottom:16px; }
        .footer { background:#f9fafb; border-top:1px solid #e5e7eb; padding:16px 32px; font-size:.78rem; color:#9ca3af; text-align:center; }
        .url-fallback { word-break:break-all; font-size:.82rem; color:#6b7280; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>🐝 AdEMNEA</h1>
            <p>Beehive Monitoring System</p>
        </div>
        <div class="body">
            <p>Hello,</p>
            <p>We received a request to reset the password for the AdEMNEA account associated with
               <strong>{{ $email }}</strong>.</p>
            <p>Click the button below to set a new password:</p>

            <p style="text-align:center;">
                <a href="{{ $resetUrl }}" class="btn">Reset My Password</a>
            </p>

            <div class="expiry">
                ⏱ This link will expire in <strong>{{ $expiresInMinutes }} minutes</strong>.
            </div>

            <p>If the button above doesn't work, copy and paste this URL into your browser:</p>
            <p class="url-fallback">{{ $resetUrl }}</p>

            <p>If you did not request a password reset, no action is required.
               Your password will remain unchanged.</p>

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
