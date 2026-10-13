<x-mail-layout heading="Tu suscripción está activa">
    <p style="margin:0 0 14px;">¡Listo, {{ $user->name }}! Tu pago se confirmó correctamente.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 18px;background:#F4F4F5;border-radius:10px;">
        <tr>
            <td style="padding:16px 20px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#3F3F46;">
                <strong style="color:#18181B;">Plan:</strong> {{ $plan->name }}<br>
                <strong style="color:#18181B;">Valor:</strong> {{ $plan->priceFormatted() }} CLP / mes
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px;">
        Ya tienes acceso a todos los videos de <strong>nivel {{ $plan->level }}</strong> o inferior.
    </p>

    <x-mail-button :url="route('videos.index')" label="Ir al catálogo" />

    <p style="margin:0;">Puedes cambiar o cancelar tu plan cuando quieras desde tu panel.</p>
</x-mail-layout>
