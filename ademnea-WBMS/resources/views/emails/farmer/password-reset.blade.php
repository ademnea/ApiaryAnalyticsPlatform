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
    <p>Enter this six-digit code in the app to reset your password:</p>
    <p style="font-size: 28px; font-weight: bold; letter-spacing: 6px;">{{ $code }}</p>
    <p>If you did not request a password reset, please ignore this email.</p>
    <p>This code expires in 10 minutes and can only be used once.</p>
    <p>Regards,<br>The AdEMNEA Team</p>
</body>
</html>