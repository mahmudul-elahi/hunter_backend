@extends('emails.layout')

@section('title', 'Welcome to Picks Empire!')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, welcome aboard! Your email has been verified and your account is ready.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f4f6f8;border-radius:8px;padding:20px 24px;">
                <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#1a1a1a;">Here's what you can do:</p>
                <p style="margin:0 0 8px;font-size:14px;color:#555555;line-height:1.6;">
                    ✓ &nbsp;Browse the latest predictions from our experts
                </p>
                <p style="margin:0 0 8px;font-size:14px;color:#555555;line-height:1.6;">
                    ✓ &nbsp;Subscribe to unlock premium predictions
                </p>
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    ✓ &nbsp;Track results and monitor win rates
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        If you have any questions, our support team is always happy to help.
    </p>
@endsection
