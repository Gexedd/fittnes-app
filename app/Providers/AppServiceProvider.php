<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\MercadoPagoGateway;
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
                ),
                default => new FakeGateway,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
