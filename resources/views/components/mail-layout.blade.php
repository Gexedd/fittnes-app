@props(['heading' => null])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $heading ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F4F5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F4F5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#FFFFFF;border:1px solid #E4E4E7;border-radius:14px;overflow:hidden;">
                    {{-- Cabecera con la marca --}}
                    <tr>
                        <td style="background:#0B0B0F;border-bottom:3px solid #FF5A1F;padding:20px 28px;">
                            <span style="font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:800;letter-spacing:1.5px;color:#FFFFFF;text-transform:uppercase;">
                                Fit<span style="color:#B6FF00;">nes</span>
                            </span>
                        </td>
                    </tr>

                    {{-- Contenido --}}
                    <tr>
                        <td style="padding:32px 28px 8px 28px;font-family:Arial,Helvetica,sans-serif;">
                            @if ($heading)
                                <h1 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;color:#18181B;">
                                    {{ $heading }}
                                </h1>
                            @endif

                            <div style="font-size:15px;line-height:1.65;color:#3F3F46;">
                                {{ $slot }}
                            </div>
                        </td>
                    </tr>

                    {{-- Pie --}}
                    <tr>
                        <td style="padding:20px 28px 24px 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#71717A;border-top:1px solid #E4E4E7;">
                            {{ config('app.name') }} — Entrenamientos en video.
                            <br>
                            Recibiste este correo porque tienes una cuenta en {{ config('app.name') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
