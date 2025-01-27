<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .email-container { padding: 20px; background-color: #f4f4f4; max-width: 600px; margin: 0 auto; }
        .reset-link { display: inline-block; background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="email-container">
        <h1>Password Reset Request</h1>
        <p>Hello,</p>
        <p>We received a request to reset your password. Click the button below to reset your password:</p>
        <a href="{{ $resetUrl }}" class="reset-link">Reset Password</a>
        <p>If you did not request this, please ignore this email.</p>
    </div>
</body>
</html>
