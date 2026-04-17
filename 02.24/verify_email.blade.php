<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your e-mail – Magic Fridge</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #16a34a; padding: 28px 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; letter-spacing: .5px; }
        .body { padding: 32px; color: #333; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: #16a34a; color: #fff !important; text-decoration: none;
               padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: bold; }
        .fallback { font-size: 12px; color: #888; word-break: break-all; }
        .footer { background: #f9f9f9; padding: 16px 32px; font-size: 12px; color: #aaa; text-align: center; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🧊 Magic Fridge</h1>
    </div>
    <div class="body">
        <p>Hi <strong>{{ $fullName }}</strong>,</p>
        <p>Thanks for registering! Please click the button below to verify your e-mail address and activate your account.</p>

        <div class="btn-wrap">
            <a class="btn" href="{{ route('verify.email', ['token' => $token]) }}">
                Verify my e-mail
            </a>
        </div>

        <p>If the button doesn't work, copy and paste this link into your browser:</p>
        <p class="fallback">{{ route('verify.email', ['token' => $token]) }}</p>

        <p>If you did not create a Magic Fridge account, you can safely ignore this e-mail.</p>
        <p>The Magic Fridge team</p>
    </div>
    <div class="footer">
        This e-mail was sent automatically. Please do not reply.
    </div>
</div>
</body>
</html>
