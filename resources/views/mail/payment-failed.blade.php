<x-mail-layout heading="No pudimos cobrar tu suscripción">
    <p style="margin:0 0 14px;">Hola {{ $user->name }}, el cobro de tu plan <strong>{{ $plan->name }}</strong> no se pudo completar.</p>

    <p style="margin:0 0 14px;">
        Por eso <strong>pausamos tu acceso</strong> hasta que el pago se regularice.
        Tu progreso y tu historial siguen guardados: no pierdes nada.
    </p>

    <p style="margin:0 0 14px;">
        Lo más rápido es actualizar el medio de pago en Mercado Pago y volver a suscribirte.
    </p>

    <x-mail-button :url="route('planes')" label="Reactivar mi plan" />

    <p style="margin:0;">Si ya realizaste el pago, puedes ignorar este mensaje.</p>
</x-mail-layout>
