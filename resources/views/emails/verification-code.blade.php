<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hirely verification code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Hello,</p>

    <p>Your Hirely verification code is:</p>

    <p style="font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #16a34a;">
        {{ $code }}
    </p>

    <p>This code will expire in {{ $expiryMinutes }} minutes.</p>

    <p>If you did not request this code, please ignore this email.</p>

    <p>Regards,<br>Hirely</p>
</body>
</html>
