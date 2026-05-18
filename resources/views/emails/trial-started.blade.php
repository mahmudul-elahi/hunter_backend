@extends('emails.layout')

@section('title', 'Your Trial Has Started')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, welcome to Picks Empire Premium!
    </p>
    <p style="margin:0 0 28px;font-size:15px;color:#444444;line-height:1.6;">
        Your <strong>3-day free trial</strong> is now active. You have full access to all premium picks and predictions during this period.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f4f6f8;border-left:4px solid #00C853;border-radius:4px;padding:16px 20px;">
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    Your trial ends in <strong>3 days</strong>. After that, your subscription will continue automatically.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        If you did not sign up for Picks Empire, please disregard this message.
    </p>
@endsection
