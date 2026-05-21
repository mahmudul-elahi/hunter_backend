@extends('emails.layout')

@section('title', 'New Prediction Available')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, a new prediction has just been posted!
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#f4f6f8;border-radius:8px;padding:20px 24px;">
                <p style="margin:0 0 6px;font-size:13px;color:#999999;text-transform:uppercase;letter-spacing:1px;">New Prediction</p>
                <p style="margin:0;font-size:18px;font-weight:700;color:#1a1a1a;">{{ $prediction->title }}</p>
                @if($prediction->category)
                    <p style="margin:8px 0 0;font-size:13px;color:#00C853;">{{ $prediction->category->name }}</p>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        Log in to Picks Empire to view the full details and place your prediction.
    </p>
@endsection
