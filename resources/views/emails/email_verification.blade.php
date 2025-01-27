<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
</head>
<body>
    <h1>Email Verification</h1>
    <p>Thank you for registering with us. Please verify your email address by clicking the link below:</p>
    <a href="{{ $verificationUrl }}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none;">Verify Email</a>
    <p>If you did not register, please ignore this email.</p>
</body>
</html>
