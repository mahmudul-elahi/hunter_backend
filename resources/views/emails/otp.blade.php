@extends('emails.layout')

@section('title', $type === 'password_reset' ? 'Password Reset Request' : 'Your OTP Code')

@section('content')
    @if($type === 'password_reset')
        <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
            We received a request to reset your password. Use the code below to proceed with your password reset.
        </p>
    @else
        <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
            We received a request for your email verification code. Use the code below to verify your account.
        </p>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center" style="padding:8px 0 28px;">
                <div style="display:inline-block;background-color:#00C853;padding:16px 48px;border-radius:6px;">
                    <span style="font-size:36px;font-weight:700;color:#ffffff;letter-spacing:10px;">{{ $code }}</span>
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:14px;color:#666666;line-height:1.6;">
        This code expires in <strong>10 minutes</strong>.
    </p>
    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        If you did not request this, please ignore this email.
    </p>
@endsection
