@extends('emails.layout')

@section('title', 'Prediction Result')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, the result for your prediction is in!
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td align="center" style="padding:8px 0;">
                <div style="display:inline-block;background-color:#f4f6f8;border-radius:8px;padding:20px 40px;text-align:center;">
                    <p style="margin:0 0 6px;font-size:13px;color:#999999;text-transform:uppercase;letter-spacing:1px;">{{ $prediction->title }}</p>
                    <p style="margin:0;font-size:28px;font-weight:700;color:{{ $prediction->status === 'win' ? '#00C853' : '#f44336' }};">
                        {{ strtoupper($prediction->status) }}
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        Log in to Picks Empire to view the full details and explore more predictions.
    </p>
@endsection
