<?php

use App\Http\Controllers\Auth\AuthSessionController;
use App\Http\Controllers\Hub\TransactionController;
use App\Http\Controllers\Hub\WhatsappLinkController;
use App\Http\Controllers\HubHomeController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('hub.home')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthSessionController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthSessionController::class, 'storeRegister'])->name('register.store');
    Route::get('/login', [AuthSessionController::class, 'createLogin'])->name('login');
    Route::post('/login', [AuthSessionController::class, 'storeLogin'])->name('login.store');
});

Route::post('/logout', [AuthSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::redirect('/app/transactions', '/hub/transactions');

Route::middleware(['auth', SetTenantContext::class])->prefix('hub')->group(function () {
    Route::get('/', HubHomeController::class)->name('hub.home');
    Route::get('/whatsapp', [WhatsappLinkController::class, 'show'])->name('hub.whatsapp');
    Route::post('/whatsapp/otp', [WhatsappLinkController::class, 'issue'])->name('hub.whatsapp.otp');
    Route::post('/whatsapp/confirm-dev', [WhatsappLinkController::class, 'confirmDev'])->name('hub.whatsapp.confirm-dev');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('hub.transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('hub.transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('hub.transactions.store');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('hub.transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('hub.transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('hub.transactions.destroy');
});
