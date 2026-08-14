<?php

namespace Webkul\WhatsApp\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppMediaService
{
    public function __construct(protected WhatsAppService $whatsAppService) {}

    /**
     * Given an inbound message's media ID, download it from Meta and
     * store it on the configured disk. Returns the publicly accessible
     * URL to save on the WhatsAppMessage row, or null on failure.
     */
    public function fetchAndStore(string $mediaId, string $type): ?string
    {
        $meta = $this->whatsAppService->getMediaMeta($mediaId);

        if (! $meta || empty($meta['url'])) {
            return null;
        }

        $bytes = $this->whatsAppService->downloadMediaBytes($meta['url']);

        if ($bytes === null) {
            return null;
        }

        $extension = $this->extensionFromMime($meta['mime_type'] ?? null, $type);
        $filename = 'whatsapp-media/'.date('Y/m/d').'/'.Str::uuid().'.'.$extension;
        $disk = config('whatsapp.media_disk', 'public');

        $stored = Storage::disk($disk)->put($filename, $bytes);

        if (! $stored) {
            Log::error('WhatsApp media store failed', ['media_id' => $mediaId, 'filename' => $filename]);

            return null;
        }

        return Storage::disk($disk)->url($filename);
    }

    protected function extensionFromMime(?string $mime, string $type): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
        ];

        return $map[$mime] ?? match ($type) {
            'image' => 'jpg',
            'video' => 'mp4',
            'audio' => 'ogg',
            'document' => 'pdf',
            default => 'bin',
        };
    }
}
