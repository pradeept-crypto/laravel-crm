<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Api\VoIPWebhookController;
use Webkul\Admin\Http\Controllers\Api\WebFormApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::post('leads/web-form', [WebFormApiController::class, 'store'])->name('api.leads.web_form');
    Route::post('voip/call-log', [VoIPWebhookController::class, 'logCall'])->name('api.voip.call_log');
});
