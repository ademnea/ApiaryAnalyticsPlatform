<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset</title>
</head>
<body>
    <h1>Reset Your Password</h1>
    <p>Hello {{ $name }},</p>
    <p>We received a request to reset your password for your AdEMNEA account.</p>
    <p>Click the button below to reset your password:</p>
    <p>
        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 10px 20px; background-color: #2a9d8f; color: #fff; text-decoration: none; border-radius: 4px;">
            Reset Password
        </a>
    </p>
    <p>If you did not request a password reset, please ignore this email.</p>
    <p>This link will expire in 60 minutes.</p>
    <p>Regards,<br>The AdEMNEA Team</p>
</body>
</html>