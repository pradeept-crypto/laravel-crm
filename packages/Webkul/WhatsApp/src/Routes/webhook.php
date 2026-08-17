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
        $host = request('host') ?: (config('mail.mailers.smtp.host') ?: 'smtp.gmail.com');
        $port = (int) (request('port') ?: (config('mail.mailers.smtp.port') ?: 587));
        $encryption = request('encryption') ?: (config('mail.mailers.smtp.encryption') ?: 'tls');
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');
        $from = config('mail.from.address');

        // Dynamically configure transport for test
        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport($host, $port, $encryption === 'ssl');
        $transport->setUsername($username);
        $transport->setPassword($password);

        $mailer = new \Symfony\Component\Mailer\Mailer($transport);
        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($from, 'AUURA CRM'))
            ->to($to)
            ->subject('AUURA CRM Test Email ' . now())
            ->text("Test email from AUURA CRM to {$to} via {$host}:{$port} ({$encryption}) at " . now());

        $mailer->send($email);

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
                'host' => request('host') ?: config('mail.mailers.smtp.host'),
                'port' => request('port') ?: config('mail.mailers.smtp.port'),
                'encryption' => request('encryption') ?: config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from' => config('mail.from.address'),
            ]
        ], 500);
    }
});
