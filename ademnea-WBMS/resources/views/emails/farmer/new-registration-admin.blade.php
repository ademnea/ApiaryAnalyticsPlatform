<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Farmer Registration Awaiting Approval</title>
</head>
<body>
    <h1>A farmer registration is awaiting approval</h1>
    <p>A new farmer has registered through the AdEMNEA mobile app and is waiting in the pending queue.</p>
    <ul>
        <li><strong>Name:</strong> {{ $name }}</li>
        <li><strong>Email:</strong> {{ $email }}</li>
    </ul>
    <p>Verify the applicant against the project participant records, assign their apiaries, then approve or reject the registration.</p>
    <p><a href="{{ $pendingUrl }}">Review pending registrations</a></p>
    <p>Regards,<br>The AdEMNEA System</p>
</body>
</html>
