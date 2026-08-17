<?php

use Illuminate\Support\Facades\Route;
use Webkul\Email\InboundEmailProcessor\WebklexImapEmailProcessor;
use Webkul\Email\Repositories\AttachmentRepository;
use Webkul\Email\Repositories\EmailRepository;
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

Route::get('/webhook/test-imap', function () {
    try {
        $host = core()->getConfigData('email.imap.account.host') ?: config('imap.accounts.default.host');
        $port = core()->getConfigData('email.imap.account.port') ?: config('imap.accounts.default.port');
        $encryption = core()->getConfigData('email.imap.account.encryption') ?: config('imap.accounts.default.encryption');
        $username = core()->getConfigData('email.imap.account.username') ?: config('imap.accounts.default.username');
        $password = core()->getConfigData('email.imap.account.password') ?: config('imap.accounts.default.password');

        $emailRepo = app(EmailRepository::class);
        $attachRepo = app(AttachmentRepository::class);
        $processor = new WebklexImapEmailProcessor($emailRepo, $attachRepo);
        $processor->processMessagesFromAllFolders();

        return response()->json([
            'status' => 'success',
            'message' => 'IMAP connected and processed messages successfully!',
            'build' => 'v3-direct-instantiation',
            'config' => [
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'username' => $username,
                'has_password' => ! empty($password),
            ],
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'status' => 'error',
            'error_message' => $e->getMessage(),
            'error_class' => get_class($e),
            'build' => 'v3-direct-instantiation',
            'config' => [
                'host' => core()->getConfigData('email.imap.account.host'),
                'port' => core()->getConfigData('email.imap.account.port'),
                'username' => core()->getConfigData('email.imap.account.username'),
            ],
        ], 500);
    }
});
