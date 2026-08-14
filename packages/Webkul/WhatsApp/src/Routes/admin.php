<?php

use Illuminate\Support\Facades\Route;
use Webkul\WhatsApp\Http\Controllers\ChatController;

Route::prefix('whatsapp')->group(function () {
    Route::get('', [ChatController::class, 'index'])->name('admin.whatsapp.index');

    Route::get('messages', [ChatController::class, 'thread'])->name('admin.whatsapp.messages');

    Route::get('lead/{lead_id}/messages', [ChatController::class, 'thread'])->name('admin.whatsapp.thread');

    Route::post('send', [ChatController::class, 'send'])->name('admin.whatsapp.send');
});
