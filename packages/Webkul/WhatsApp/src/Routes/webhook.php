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

Route::get('/webhook/test-email', function () {
    try {
        $to = request('to', 'saravanan.sr@kaditinnovations.com');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $encryption = config('mail.mailers.smtp.encryption');
        $username = config('mail.mailers.smtp.username');
        $from = config('mail.from.address');

        \Illuminate\Support\Facades\Mail::raw("Test email from AUURA CRM to {$to} via {$host}:{$port} ({$encryption}) at " . now(), function ($message) use ($to) {
            $message->to($to)->subject('AUURA CRM Test Email ' . now());
        });

        return response()->json([
            'status' => 'success',
            'message' => "Email sent successfully to {$to}!",
            'smtp_config' => [
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'username' => $username,
                'from' => $from,
            ]
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'error_message' => $e->getMessage(),
            'error_class' => get_class($e),
            'smtp_config' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from' => config('mail.from.address'),
            ]
        ], 500);
    }
});
