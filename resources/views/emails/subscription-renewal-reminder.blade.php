@extends('emails.layout')

@section('title', 'Subscription Renewal Reminder')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, just a heads-up that your Picks Empire subscription is renewing soon.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f0fdf4;border-left:4px solid #00C853;border-radius:4px;padding:16px 20px;">
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    Your subscription will automatically renew on <strong>{{ $renewalDate->format('F j, Y') }}</strong>.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Please make sure your payment method is up to date to avoid any interruption to your premium access.
    </p>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        If you'd like to cancel before the renewal date, you can do so from your account settings.
    </p>
@endsection
