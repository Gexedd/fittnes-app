<x-mail-layout :heading="'¡Bienvenido, '.$user->name.'!'">
    <p style="margin:0 0 14px;">Tu cuenta en <strong>Fitnes</strong> está lista.</p>

    <p style="margin:0 0 14px;">
        Ya puedes entrar al catálogo y explorar los entrenamientos. Si todavía no tienes una
        suscripción, elige el nivel que mejor se acomode a tu rutina.
    </p>

    <x-mail-button :url="route('planes')" label="Ver planes y precios" />

    <p style="margin:0;">¡Nos vemos entrenando!</p>
</x-mail-layout>
