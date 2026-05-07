<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You've been invited to Chrono</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f5; margin: 0; padding: 40px 0; }
        .wrapper { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 40px; }
        h1 { font-size: 20px; color: #18181b; margin: 0 0 16px; }
        p { font-size: 15px; color: #3f3f46; line-height: 1.6; margin: 0 0 24px; }
        .btn { display: inline-block; background: #18181b; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 15px; font-weight: 500; }
        .footer { margin-top: 32px; font-size: 13px; color: #a1a1aa; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>You've been invited to Chrono</h1>
        <p>An admin has invited <strong>{{ $email }}</strong> to join their organisation on Chrono, a time tracking app.</p>
        <p>Click the button below to set up your account. This link expires in 7 days.</p>
        <a href="{{ $acceptUrl }}" class="btn">Accept invitation</a>
        <div class="footer">
            If you weren't expecting this invitation, you can ignore this email.
        </div>
    </div>
</body>
</html>
