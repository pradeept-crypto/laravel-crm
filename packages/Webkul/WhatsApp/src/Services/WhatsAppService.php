<?php

namespace Webkul\WhatsApp\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;

    public function __construct(
        protected ?string $phoneNumberId,
        protected ?string $accessToken,
        protected string $apiVersion = 'v25.0',
    ) {
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}";
    }

    /**
     * Send a free-form text message.
     */
    public function sendText(string $to, string $body): array
    {
        return $this->post('/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeNumber($to),
            'type' => 'text',
            'text' => ['body' => $body],
        ]);
    }

    /**
     * Send a pre-approved message template.
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'en_US', array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeNumber($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];

        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->post('/messages', $payload);
    }

    /**
     * Send a media message (image, document, video, audio) by public URL.
     */
    public function sendMedia(string $to, string $type, string $mediaUrl, ?string $caption = null): array
    {
        $mediaObject = ['link' => $mediaUrl];

        if ($caption && in_array($type, ['image', 'video', 'document'], true)) {
            $mediaObject['caption'] = $caption;
        }

        return $this->post('/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeNumber($to),
            'type' => $type,
            $type => $mediaObject,
        ]);
    }

    /**
     * Mark an inbound message as read.
     */
    public function markAsRead(string $waMessageId): array
    {
        return $this->post('/messages', [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $waMessageId,
        ]);
    }

    /**
     * Resolve a Meta media ID to temporary download URL.
     */
    public function getMediaMeta(string $mediaId): ?array
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$mediaId}";

        $response = Http::withToken($this->accessToken)
            ->withHeaders(['User-Agent' => 'curl/7.68.0'])
            ->timeout(15)
            ->get($url);

        if ($response->failed()) {
            Log::error('WhatsApp media meta lookup failed', [
                'media_id' => $mediaId,
                'response' => $response->json(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Download the raw bytes for a media URL.
     */
    public function downloadMediaBytes(string $mediaUrl): ?string
    {
        $response = Http::withToken($this->accessToken)
            ->withHeaders(['User-Agent' => 'curl/7.68.0'])
            ->timeout(30)
            ->get($mediaUrl);

        if ($response->failed()) {
            Log::error('WhatsApp media download failed', ['url' => $mediaUrl]);

            return null;
        }

        return $response->body();
    }

    protected function post(string $path, array $payload): array
    {
        $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->post($this->baseUrl.$path, $payload);

        if ($response->failed()) {
            Log::error('WhatsApp API error', [
                'payload' => $payload,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);
        }

        return [
            'ok' => $response->successful(),
            'body' => $response->json(),
        ];
    }

    /**
     * Normalize number to international format.
     * Automatically prepends '91' if a 10-digit Indian number is provided.
     */
    public function normalizeNumber(string $number): string
    {
        $cleaned = ltrim(preg_replace('/[^0-9]/', '', $number), '0');

        if (strlen($cleaned) === 10 && preg_match('/^[6-9]/', $cleaned)) {
            $cleaned = '91'.$cleaned;
        }

        return $cleaned;
    }
}
