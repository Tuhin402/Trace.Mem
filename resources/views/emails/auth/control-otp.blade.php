<x-mail::message>
# Operations Console Verification

Hello {{ $data['user_name'] ?? 'Administrator' }},

A request was made to access the TraceMem Operations Console. Please use the following One-Time Password (OTP) to verify your identity:

<x-mail::panel>
<div style="text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 4px; padding: 10px;">
{{ $data['otp'] }}
</div>
</x-mail::panel>

This code is valid for **5 minutes**. 

If you did not request this code, please ignore this email. Your account remains secure.

Thanks,<br>
{{ config('app.name') }} Security Operations
</x-mail::message>
