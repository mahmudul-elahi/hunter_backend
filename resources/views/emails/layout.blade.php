<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Picks Empire')</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f6f8;padding:48px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:580px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="padding:32px 40px 28px;">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="Picks Empire" width="120" style="display:block;height:auto;">
                        </td>
                    </tr>

                    {{-- Title --}}
                    <tr>
                        <td align="center" style="padding:36px 40px 0;">
                            <h1 style="margin:0;font-size:22px;font-weight:700;color:#1a1a1a;">@yield('title')</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:24px 40px 40px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 40px;border-top:1px solid #eeeeee;text-align:center;">
                            <p style="margin:0;font-size:13px;color:#999999;">
                                Questions? Contact
                                <a href="mailto:support@picksempire.com" style="color:#00C853;text-decoration:none;">support@picksempire.com</a>
                            </p>
                            <p style="margin:10px 0 0;font-size:12px;color:#bbbbbb;">
                                &copy; {{ date('Y') }} Picks Empire. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
