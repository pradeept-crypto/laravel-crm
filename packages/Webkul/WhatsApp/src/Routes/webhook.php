<?php

use Illuminate\Support\Facades\Route;
use Webkul\WhatsApp\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Public webhook routes
|--------------------------------------------------------------------------
| These must NOT sit behind Krayin's admin auth middleware since Meta's
| servers call them directly. They must also be excluded from CSRF
| verification - add 'webhook/whatsapp' to the $except array in
| App\Http\Middleware\VerifyCsrfToken (or bootstrap/app.php on Laravel 11).
*/

Route::get('/webhook/whatsapp', [WebhookController::class, 'verify'])
    ->name('whatsapp.webhook.verify');

Route::post('/webhook/whatsapp', [WebhookController::class, 'handle'])
    ->name('whatsapp.webhook.handle');
