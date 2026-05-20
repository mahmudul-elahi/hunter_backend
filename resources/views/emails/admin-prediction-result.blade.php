@extends('emails.layout')

@section('title', 'Prediction Result Published')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        A prediction result has been published.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f0fdf4;border-left:4px solid #00C853;border-radius:4px;padding:16px 20px;">
                <p style="margin:0 0 8px;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>Title:</strong> {{ $prediction->title }}
                </p>
                <p style="margin:0 0 8px;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>Result:</strong>
                    <span style="text-transform:uppercase;font-weight:700;color:{{ $prediction->status === 'win' ? '#00C853' : '#e53e3e' }};">
                        {{ $prediction->status }}
                    </span>
                </p>
                <p style="margin:0;font-size:14px;color:#555555;line-height:1.6;">
                    <strong>Date:</strong> {{ now()->format('d M Y, H:i') }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        Log in to the admin panel to view full prediction details.
    </p>
@endsection
