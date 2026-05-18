@extends('emails.layout')

@section('title', 'Payment Failed')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, we were unable to process your payment.
    </p>
    <p style="margin:0 0 28px;font-size:15px;color:#444444;line-height:1.6;">
        Please update your payment method to avoid any interruption to your Picks Empire subscription.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td align="center">
                <a href="#"
                   style="display:inline-block;background-color:#00C853;color:#ffffff;padding:14px 36px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;">
                    Update Payment Method
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        This link will expire in <strong>24 hours</strong>. If you did not request this, please disregard this message.
    </p>
@endsection
