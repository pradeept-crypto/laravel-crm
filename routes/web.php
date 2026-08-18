<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Api\VoIPWebhookController;

Route::post('webhook/voip/call-log', [VoIPWebhookController::class, 'logCall'])->name('webhook.voip.call_log');
