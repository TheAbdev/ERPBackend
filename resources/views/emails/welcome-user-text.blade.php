Welcome to {{ $user->tenant->name ?? config('app.name') }}!

Hello {{ $user->name }},

Your account has been created successfully. You can now access the system using the following credentials:

Email: {{ $user->email }}
@if($password)
Password: {{ $password }}

Please change your password after your first login for security.
@else
Please use the password you set during registration.
@endif

Login URL: {{ $loginUrl }}

If you have any questions or need assistance, please don't hesitate to contact our support team.

Best regards,
{{ $user->tenant->name ?? config('app.name') }} Team

---
This is an automated message. Please do not reply to this email.

































