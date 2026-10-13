<x-mail-layout heading="Tu suscripción fue cancelada">
    <p style="margin:0 0 14px;">Hola {{ $user->name }}, confirmamos la cancelación de tu suscripción.</p>

    <p style="margin:0 0 14px;">
        Tu plan <strong>{{ $plan->name }}</strong> ya no se renovará automáticamente.
        Seguirás teniendo acceso hasta el final del período que ya pagaste.
    </p>

    <p style="margin:0 0 14px;">Si fue un error, puedes reactivarla cuando quieras:</p>

    <x-mail-button :url="route('planes')" label="Reactivar mi plan" />

    <p style="margin:0;">Gracias por haber entrenado con nosotros.</p>
</x-mail-layout>
