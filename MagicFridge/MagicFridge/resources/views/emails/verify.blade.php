<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your e-mail - Magic Fridge</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; margin: 0; padding: 0; }
        .page { background: radial-gradient(circle at top right, #6366f1 0%, #0f172a 68%, #020617 100%); padding: 44px 16px; }
        .wrapper { max-width: 560px; margin: 0 auto; background: rgba(255,255,255,0.96); border-radius: 8px; overflow: hidden; box-shadow: 0 22px 60px rgba(15,23,42,.35); }
        .header { background: linear-gradient(135deg, #6366f1, #2563eb); padding: 28px 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 32px; color: #1e293b; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: linear-gradient(135deg, #6366f1, #2563eb); color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: bold; box-shadow: 0 10px 22px rgba(37,99,235,.28); }
        .fallback { font-size: 12px; color: #475569; word-break: break-all; }
        .footer { background: #eef2ff; padding: 16px 32px; font-size: 12px; color: #475569; text-align: center; border-top: 1px solid #dbe4ff; }
    </style>
</head>
<body>
<div class="page">
    <div class="wrapper">
        <div class="header">
            <h1>Magic Fridge</h1>
        </div>
        <div class="body">
            <p>Hi <strong>{{ $name }}</strong>,</p>
            <p>Thanks for registering. Please click the button below to verify your e-mail address and activate your account.</p>

            <div class="btn-wrap">
                <a class="btn" href="{{ route('verify.email', ['token' => $token]) }}">
                    Verify my e-mail
                </a>
            </div>

            <p>If the button does not work, copy and paste this link into your browser:</p>
            <p class="fallback">{{ route('verify.email', ['token' => $token]) }}</p>

            <p>If you did not create a Magic Fridge account, you can safely ignore this e-mail.</p>
            <p>The Magic Fridge team</p>
        </div>
        <div class="footer">
            This e-mail was sent automatically. Please do not reply.
        </div>
    </div>
</div>
</body>
</html>
