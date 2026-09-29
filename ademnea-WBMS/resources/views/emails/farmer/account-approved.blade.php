<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Activated</title>
</head>
<body>
    <h1>Your AdEMNEA account is active</h1>
    <p>Hello {{ $name }},</p>
    <p>An administrator has approved your registration. You can now sign in to the AdEMNEA mobile app and view your apiaries, hive sensor readings, media and inspection records.</p>
    <p><a href="{{ $loginUrl }}">Open the AdEMNEA app</a></p>
    <p>If you have any questions, please contact the project team.</p>
    <p>Regards,<br>The AdEMNEA Team</p>
</body>
</html>
