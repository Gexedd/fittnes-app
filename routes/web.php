<?php

use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'home')->name('home');

Route::get('planes', [PlanController::class, 'index'])->name('planes');

// Catálogo público (visible sin sesión; el contenido bloqueado invita a suscribirse).
Volt::route('catalogo', 'videos.index')->name('videos.index');

// --- Suscripciones (requieren sesión) ---
Route::middleware('auth')->group(function () {
    Route::get('videos/{video:slug}', [VideoController::class, 'show'])->name('videos.show');

    Route::post('suscribirse/{plan:slug}', [SubscriptionController::class, 'store'])
        ->name('subscriptions.store');

    Route::get('suscripcion/retorno', [SubscriptionController::class, 'callback'])
        ->name('subscriptions.callback');

    Route::post('suscripcion/cancelar', [SubscriptionController::class, 'cancel'])
        ->name('subscriptions.cancel');
});

// --- Panel de administración (solo el entrenador) ---
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('metricas', 'admin.metrics.index')->name('metrics.index');
    Volt::route('videos', 'admin.videos.index')->name('videos.index');
    Volt::route('categorias', 'admin.categories.index')->name('categories.index');
});

// --- Webhooks de la pasarela (sin CSRF; se validan por firma HMAC) ---
Route::post('webhooks/mercadopago', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.mercadopago');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
