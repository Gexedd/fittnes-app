<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorio de renovación: todos los días a las 09:00.
Schedule::command('subscriptions:renewal-reminders')->dailyAt('09:00');

// Red de seguridad de pagos: los webhooks pueden perderse o no llegar, así que
// cada 5 minutos se pregunta a la pasarela por lo que quedó pendiente.
Schedule::command('subscriptions:reconcile')->everyFiveMinutes()->withoutOverlapping();
