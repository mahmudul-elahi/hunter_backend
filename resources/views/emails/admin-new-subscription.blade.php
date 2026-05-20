@extends('emails.layout')

@section('title', 'New Subscription Purchase')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        A new subscription has been purchased.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f0fdf4;border-left:4px solid #00C853;border-radius:4px;padding:16px 20px;">
                <p style="margin:0 0 8px;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}
                </p>
                <p style="margin:0 0 8px;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>Email:</strong> {{ $user->email }}
                </p>
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>Date:</strong> {{ now()->format('d M Y, H:i') }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        Log in to the admin panel to view full subscription details.
    </p>
@endsection
