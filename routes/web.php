<?php

use App\Http\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('planes', [PlanController::class, 'index'])->name('planes');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
