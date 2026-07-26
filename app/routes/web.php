<?php

use App\Http\Controllers\Auth\AuthSessionController;
use App\Http\Controllers\Hub\ConnectionController;
use App\Http\Controllers\Hub\TransactionController;
use App\Http\Controllers\Hub\WhatsappLinkController;
use App\Http\Controllers\HubHomeController;
use App\Http\Controllers\LegalController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('hub.home')
        : redirect()->route('login');
});

Route::get('/legal/open-finance', [LegalController::class, 'openFinance'])->name('legal.open-finance');
Route::get('/legal/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');

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
Route::redirect('/app/connections', '/hub/connections');

Route::middleware(['auth', SetTenantContext::class])->prefix('hub')->group(function () {
    Route::get('/', HubHomeController::class)->name('hub.home');
    Route::get('/whatsapp', [WhatsappLinkController::class, 'show'])->name('hub.whatsapp');
    Route::post('/whatsapp/otp', [WhatsappLinkController::class, 'issue'])->name('hub.whatsapp.otp');
    Route::post('/whatsapp/confirm-dev', [WhatsappLinkController::class, 'confirmDev'])->name('hub.whatsapp.confirm-dev');

    Route::get('/connections', [ConnectionController::class, 'index'])->name('hub.connections.index');
    Route::post('/connections/connect-token', [ConnectionController::class, 'connectToken'])->name('hub.connections.connect-token');
    Route::post('/connections/items', [ConnectionController::class, 'storeItem'])->name('hub.connections.items.store');
    Route::post('/connections/{item}/sync', [ConnectionController::class, 'sync'])->name('hub.connections.sync');
    Route::post('/connections/{item}/revoke', [ConnectionController::class, 'revoke'])->name('hub.connections.revoke');
    Route::get('/connections/accounts/{account}', [ConnectionController::class, 'showAccount'])->name('hub.connections.accounts.show');
    Route::patch('/connections/transactions/{transaction}/category', [ConnectionController::class, 'updateTransactionCategory'])
        ->name('hub.connections.transactions.category');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('hub.transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('hub.transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('hub.transactions.store');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('hub.transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('hub.transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('hub.transactions.destroy');
});
