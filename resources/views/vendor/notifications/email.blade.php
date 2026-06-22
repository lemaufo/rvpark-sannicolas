@php
    $logoUrl = 'https://raw.githubusercontent.com/lemaufo/rvpark-sannicolas/dev/public/logo_email.png';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f2f5;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 480px; width: 100%;">

                    {{-- Logo --}}
                    <tr>
                        <td align="center" style="padding-bottom: 28px;">
                            <img src="{{ $logoUrl }}" alt="RV Park San Nicolás" width="80" style="display: block; width: 80px; height: auto;" />
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">

                            {{-- Header --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color: #4a5d41; padding: 28px 32px; text-align: center;">
                                        @if (! empty($greeting))
                                            <h1 style="color: #ffffff; font-size: 20px; font-weight: 700; margin: 0; line-height: 1.3;">{{ $greeting }}</h1>
                                        @else
                                            @if ($level === 'error')
                                                <h1 style="color: #ffffff; font-size: 20px; font-weight: 700; margin: 0;">¡Ups!</h1>
                                            @else
                                                <h1 style="color: #ffffff; font-size: 20px; font-weight: 700; margin: 0;">¡Hola!</h1>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            {{-- Body --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding: 32px;">

                                        {{-- Intro --}}
                                        @foreach ($introLines as $line)
                                            <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 20px;">{{ $line }}</p>
                                        @endforeach

                                        {{-- Botón --}}
                                        @isset($actionText)
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 28px 0;">
                                                <tr>
                                                    <td align="center">
                                                        <a href="{{ $actionUrl }}" style="background-color: #4a5d41; color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; padding: 13px 44px; border-radius: 8px; display: inline-block;">{{ $actionText }}</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endisset

                                        {{-- Outro --}}
                                        @foreach ($outroLines as $line)
                                            <p style="color: #374151; font-size: 14px; line-height: 1.6; margin: 0 0 16px;">{{ $line }}</p>
                                        @endforeach

                                        {{-- Despedida --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 28px; border-top: 1px solid #e5e7eb;">
                                            <tr>
                                                <td style="padding-top: 20px;">
                                                    @if (! empty($salutation))
                                                        <p style="color: #6b7280; font-size: 14px; margin: 0;">{{ $salutation }}</p>
                                                    @else
                                                        <p style="color: #6b7280; font-size: 14px; margin: 0;">Saludos,<br><strong style="color: #374151;">{{ config('app.name') }}</strong></p>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>



                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding: 24px 16px;">
                            <p style="color: #9ca3af; font-size: 11px; margin: 0;">© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
