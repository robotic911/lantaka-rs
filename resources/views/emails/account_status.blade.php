<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <div style="font-family: sans-serif; color: #333;">
    <p>Dear {{ $user->Account_Name }},</p>
    <p>Below are the details of your account registration:</p>

    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
        <strong>Account Information</strong><br>
        Username: {{ $user->Account_Username }}<br>
        Phone: {{ $user->Account_Phone }}<br>
        Date Created: {{ $user->created_at->format('d/m/Y') }}<br>
        Full Name: {{ $user->Account_Name }}
    </div>

    <p><strong>Registration Status:</strong></p>
    @if($status === 'approved')
        <p style="color: green; font-weight: bold;">Approved</p>
        <p>(Your account has been successfully verified. You may now log in to the system.)</p>
    @else
        <p style="color: #d9534f; font-weight: bold;">Declined</p>
        <p>(Unfortunately, your registration could not be approved at this time. Please ensure all your details match your official identification.)</p>
    @endif

    <p>Warm regards,<br><strong>{{ $campusName }}</strong></p>
</div>
</body>
</html>