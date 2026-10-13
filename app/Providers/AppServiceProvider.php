<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Video\VideoPlayback;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\MercadoPagoGateway;
use App\Services\Video\BunnyPlayback;
use App\Services\Video\NullPlayback;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Elige el driver de pagos según PAYMENT_DRIVER. Todo el resto de la
        // aplicación depende solo de la interfaz PaymentGateway.
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            return match (config('services.payments.driver')) {
                'mercadopago' => new MercadoPagoGateway(
                    accessToken: (string) config('services.mercadopago.access_token'),
                    baseUrl: (string) config('services.mercadopago.base_url'),
                    sandbox: (bool) config('services.mercadopago.sandbox'),
                    testPayerEmail: config('services.mercadopago.test_payer_email') ?: null,
                ),
                default => new FakeGateway,
            };
        });

        // Reproducción: "bunny" genera URLs firmadas; sin credenciales se usa
        // el driver nulo y la vista muestra un marcador.
        $this->app->singleton(VideoPlayback::class, function (): VideoPlayback {
            return match (config('services.video.driver')) {
                'bunny' => new BunnyPlayback(
                    libraryId: (string) config('services.bunny.library_id'),
                    tokenKey: (string) config('services.bunny.token_key'),
                    cdnHostname: (string) config('services.bunny.cdn_hostname'),
                ),
                default => new NullPlayback,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // En producción (detrás del proxy de Forge) forzamos https para que los
        // enlaces de correos, webhooks y redirecciones se generen correctamente.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
