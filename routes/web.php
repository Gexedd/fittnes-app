<?php

use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'home')->name('home');

Route::get('planes', [PlanController::class, 'index'])->name('planes');

// --- Suscripciones (requieren sesión) ---
Route::middleware('auth')->group(function () {
    Route::post('suscribirse/{plan:slug}', [SubscriptionController::class, 'store'])
        ->name('subscriptions.store');

    Route::get('suscripcion/retorno', [SubscriptionController::class, 'callback'])
        ->name('subscriptions.callback');

    Route::post('suscripcion/cancelar', [SubscriptionController::class, 'cancel'])
        ->name('subscriptions.cancel');
});

// --- Panel de administración (solo el entrenador) ---
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('videos', 'admin.videos.index')->name('videos.index');
    Volt::route('categorias', 'admin.categories.index')->name('categories.index');
});

// --- Webhooks de la pasarela (sin CSRF; se validan por firma HMAC) ---
Route::post('webhooks/mercadopago', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.mercadopago');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
