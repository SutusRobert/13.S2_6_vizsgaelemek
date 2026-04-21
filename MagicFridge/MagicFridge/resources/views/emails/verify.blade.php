<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
</head>
<body>
    <h1>Hello {{ $name }}!</h1>
    <p>Thank you for registering with MagicFridge. Please verify your email address by clicking the link below:</p>
    <a href="{{ url('/verify-email/' . $token) }}">Verify Email</a>
    <p>If you did not create an account, no further action is required.</p>
</body>
</html>