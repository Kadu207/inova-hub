<?php

use App\Http\Controllers\Webhooks\PluggyWebhookController;
use App\Http\Controllers\Webhooks\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'receive']);
Route::get('/webhooks/pluggy', [PluggyWebhookController::class, 'ping']);
Route::post('/webhooks/pluggy', [PluggyWebhookController::class, 'receive']);
