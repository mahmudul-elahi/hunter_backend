@extends('emails.layout')

@section('title', 'Promo Code Applied')

@section('content')
    <p style="margin:0 0 20px;font-size:15px;color:#444444;line-height:1.6;">
        Hi <strong>{{ $user->first_name }}</strong>, your promo code has been applied successfully!
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
        <tr>
            <td align="center" style="padding:8px 0;">
                <div style="display:inline-block;background-color:#f4f6f8;border:2px dashed #00C853;border-radius:8px;padding:18px 36px;text-align:center;">
                    <p style="margin:0 0 4px;font-size:12px;color:#999999;text-transform:uppercase;letter-spacing:1px;">Promo Code</p>
                    <p style="margin:0;font-size:24px;font-weight:700;color:#00C853;letter-spacing:4px;">{{ $promoCode->code }}</p>
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;color:#999999;line-height:1.6;">
        Enjoy your discounted subscription to Picks Empire. If you have any questions, our support team is here to help.
    </p>
@endsection
