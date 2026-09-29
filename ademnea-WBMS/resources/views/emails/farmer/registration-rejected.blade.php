<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>About Your Registration</title>
</head>
<body>
    <h1>About your AdEMNEA registration</h1>
    <p>Hello {{ $name }},</p>
    <p>We were not able to approve your registration for the AdEMNEA Beehive Monitoring System at this time.</p>
    @if (!empty($reason))
        <p>{{ $reason }}</p>
    @endif
    <p>If you believe this is a mistake, or you are a participant in the project, please contact the project team at {{ $contactEmail }} and we will follow up with you.</p>
    <p>Regards,<br>The AdEMNEA Team</p>
</body>
</html>
