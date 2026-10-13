<x-mail-layout heading="Tu suscripción se renueva pronto">
    <p style="margin:0 0 14px;">Hola {{ $user->name }}, te avisamos con tiempo.</p>

    <p style="margin:0 0 14px;">
        Tu plan <strong>{{ $plan->name }}</strong> ({{ $plan->priceFormatted() }} CLP / mes)
        se renueva en <strong>{{ $days }} día(s)</strong>
        @if ($subscription->current_period_end)
            , el {{ $subscription->current_period_end->format('d/m/Y') }}
        @endif
        .
    </p>

    <p style="margin:0 0 14px;">
        No necesitas hacer nada: el cobro se realiza automáticamente con tu medio de pago guardado.
        Si quieres cambiar de plan, puedes hacerlo desde tu panel.
    </p>

    <x-mail-button :url="route('dashboard')" label="Ver mi suscripción" />

    <p style="margin:0;">¡Que no se corte la racha!</p>
</x-mail-layout>
