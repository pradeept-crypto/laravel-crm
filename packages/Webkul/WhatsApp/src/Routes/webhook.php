<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Webklex\IMAP\Facades\Client;
use Webkul\Email\InboundEmailProcessor\WebklexImapEmailProcessor;
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

Route::get('/webhook/diagnose-imap', function () {
    try {
        $processor = app(WebklexImapEmailProcessor::class);
        $client = Client::make([
            'host' => core()->getConfigData('email.imap.account.host') ?: config('imap.accounts.default.host'),
            'port' => core()->getConfigData('email.imap.account.port') ?: config('imap.accounts.default.port'),
            'protocol' => 'imap',
            'encryption' => core()->getConfigData('email.imap.account.encryption') ?: config('imap.accounts.default.encryption'),
            'validate_cert' => (bool) (core()->getConfigData('email.imap.account.validate_cert') ?? true),
            'username' => core()->getConfigData('email.imap.account.username') ?: config('imap.accounts.default.username'),
            'password' => core()->getConfigData('email.imap.account.password') ?: config('imap.accounts.default.password'),
        ]);

        $client->connect();
        $folders = $client->getFolders();
        $folderList = [];
        $messagesFound = [];

        foreach ($folders as $folder) {
            $folderList[] = $folder->name;
            if (strtolower($folder->name) === 'inbox') {
                $inboxMessages = $folder->messages()->all()->setFetchBody(true)->limit(10)->get();
                foreach ($inboxMessages as $msg) {
                    $from = $msg->getFrom() ? $msg->getFrom()->first() : null;

                    $processResult = 'not_run';
                    try {
                        $processor->processMessage($msg);
                        $processResult = 'processed_ok';
                    } catch (Throwable $ex) {
                        $processResult = 'error: '.$ex->getMessage().' in '.$ex->getFile().':'.$ex->getLine();
                    }

                    $messagesFound[] = [
                        'uid' => $msg->getUid(),
                        'subject' => (string) $msg->getSubject(),
                        'from_mail' => $from ? $from->mail : null,
                        'from_name' => $from ? $from->personal : null,
                        'message_id' => (string) $msg->getMessageId(),
                        'in_reply_to' => (string) ($msg->getInReplyTo() ? $msg->getInReplyTo()->first() : null),
                        'date' => (string) ($msg->getDate() ? $msg->getDate()->toDate() : null),
                        'process_result' => $processResult,
                    ];
                }
            }
        }

        $recentDbEmails = DB::table('emails')
            ->select('id', 'parent_id', 'lead_id', 'subject', 'from', 'user_type', 'folders', 'message_id', 'created_at')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'connected' => $client->isConnected(),
            'folders' => $folderList,
            'inbox_messages_count' => count($messagesFound),
            'inbox_messages' => $messagesFound,
            'recent_db_emails' => $recentDbEmails,
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
