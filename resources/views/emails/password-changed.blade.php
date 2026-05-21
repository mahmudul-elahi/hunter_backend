@extends('emails.layout')

@section('title', 'Password Changed')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, your Picks Empire account password was recently changed.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#fff8f0;border-left:4px solid #FF6D00;border-radius:4px;padding:16px 20px;">
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    If you made this change, no further action is needed.
                    If you did <strong>not</strong> make this change, please contact our support team immediately.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        To report an issue, email us at
        <a href="mailto:support@picksempire.com" style="color:#00C853;text-decoration:none;">support@picksempire.com</a>
    </p>
@endsection
