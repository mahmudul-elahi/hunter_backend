@extends('emails.layout')

@section('title', 'Subscription Cancelled')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, your Picks Empire subscription has been cancelled.
    </p>
    <p style="margin:0 0 28px;font-size:15px;color:#444444;line-height:1.6;">
        We're sorry to see you go. You will retain access until the end of your current billing period.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td align="center">
                <a href="#"
                   style="display:inline-block;background-color:#00C853;color:#ffffff;padding:14px 36px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;">
                    Reactivate Subscription
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        If this was a mistake or you change your mind, you can reactivate at any time.
    </p>
@endsection
