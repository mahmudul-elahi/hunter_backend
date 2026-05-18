@extends('emails.layout')

@section('title', 'Payment Successful')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, your payment has been processed successfully.
    </p>
    <p style="margin:0 0 28px;font-size:15px;color:#444444;line-height:1.6;">
        Thank you for subscribing to Picks Empire. Your premium access is active and ready to use.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f0fdf4;border-left:4px solid #00C853;border-radius:4px;padding:16px 20px;">
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    ✓ &nbsp;Payment received &amp; subscription is active.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        If you have any questions about your billing, feel free to reach out to our support team.
    </p>
@endsection
