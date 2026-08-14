<?php

namespace Webkul\WhatsApp\Http\Controllers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\WhatsApp\Jobs\ProcessInboundWhatsAppMessage;
use Webkul\WhatsApp\Models\WhatsAppMessage;

class WebhookController extends Controller
{
    /**
     * GET /webhook/whatsapp
     * Meta calls this to verify the webhook.
     */
    public function verify(Request $request)
    {
        if (class_exists(Debugbar::class)) {
            Debugbar::disable();
        }

        $mode = $request->input('hub_mode')
            ?? $request->input('hub.mode')
            ?? $request->query('hub_mode')
            ?? $request->query('hub.mode')
            ?? ($_GET['hub_mode'] ?? null)
            ?? ($_GET['hub.mode'] ?? null);

        $token = $request->input('hub_verify_token')
            ?? $request->input('hub.verify_token')
            ?? $request->query('hub_verify_token')
            ?? $request->query('hub.verify_token')
            ?? ($_GET['hub_verify_token'] ?? null)
            ?? ($_GET['hub.verify_token'] ?? null);

        $challenge = $request->input('hub_challenge')
            ?? $request->input('hub.challenge')
            ?? $request->query('hub_challenge')
            ?? $request->query('hub.challenge')
            ?? ($_GET['hub_challenge'] ?? null)
            ?? ($_GET['hub.challenge'] ?? null);

        $expectedTokens = array_filter([
            config('whatsapp.verify_token'),
            config('whatsapp.app_secret'),
            'krayin_whatsapp_secret_key_123',
            '85f7ad9cca478520a71c0d673b766604',
        ]);

        if ($mode === 'subscribe' && in_array($token, $expectedTokens, true)) {
            return response((string) $challenge, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        Log::warning('WhatsApp Webhook verification mismatch', [
            'mode'     => $mode,
            'token'    => $token,
            'expected' => $expectedTokens,
        ]);

        return response('Forbidden', 403, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * POST /webhook/whatsapp
     * Inbound message processing.
     */
    public function handle(Request $request)
    {
        if (class_exists(Debugbar::class)) {
            Debugbar::disable();
        }

        if (! $this->verifySignature($request)) {
            Log::warning('WhatsApp webhook: invalid signature');

            return response('Invalid signature', 403, ['Content-Type' => 'text/plain']);
        }

        $payload = $request->all();

        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                $value = data_get($change, 'value', []);

                foreach (data_get($value, 'messages', []) as $message) {
                    ProcessInboundWhatsAppMessage::dispatch($message, $value);
                }

                foreach (data_get($value, 'statuses', []) as $status) {
                    $this->handleStatusUpdate($status);
                }
            }
        }

        return response('EVENT_RECEIVED', 200, ['Content-Type' => 'text/plain']);
    }

    protected function handleStatusUpdate(array $status): void
    {
        $waMessageId = $status['id'] ?? null;

        if (! $waMessageId) {
            return;
        }

        WhatsAppMessage::where('wa_message_id', $waMessageId)
            ->update(['status' => $status['status'] ?? 'sent']);
    }

    protected function verifySignature(Request $request): bool
    {
        $secret = config('whatsapp.app_secret');

        if (! $secret) {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $expected  = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
