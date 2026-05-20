@extends('emails.layout')

@section('title', 'Support Request')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        A new support message has been submitted.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f8f9fa;border-left:4px solid #00C853;border-radius:4px;padding:16px 20px;">
                <p style="margin:0 0 8px;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>From:</strong> {{ $user->first_name }} {{ $user->last_name }} &lt;{{ $user->email }}&gt;
                </p>
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>Subject:</strong> {{ $subject }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:14px;color:#777777;line-height:1.6;text-transform:uppercase;letter-spacing:0.05em;">
        Message
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f8f9fa;border-radius:4px;padding:16px 20px;">
                <p style="margin:0;font-size:15px;color:#444444;line-height:1.8;white-space:pre-line;">{{ $message }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        Reply directly to this email to respond to the user.
    </p>
@endsection
