<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h1 style="color: #2563eb; margin: 0;">Welcome to {{ $user->tenant->name ?? config('app.name') }}!</h1>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <p>Hello {{ $user->name }},</p>

        <p>Your account has been created successfully. You can now access the system using the following credentials:</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
            @if($password)
            <p style="margin: 5px 0;"><strong>Password:</strong> {{ $password }}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #6b7280;">
                <em>Please change your password after your first login for security.</em>
            </p>
            @else
            <p style="margin: 5px 0; color: #6b7280;">
                <em>Please use the password you set during registration.</em>
            </p>
            @endif
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginUrl }}" 
               style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                Login to Your Account
            </a>
        </div>

        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>{{ $user->tenant->name ?? config('app.name') }} Team</strong>
        </p>
    </div>

    <div style="margin-top: 20px; padding: 15px; text-align: center; color: #6b7280; font-size: 12px;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>






