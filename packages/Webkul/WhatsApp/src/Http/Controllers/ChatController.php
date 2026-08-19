<?php

namespace Webkul\WhatsApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\WhatsApp\Models\WhatsAppMessage;
use Webkul\WhatsApp\Services\WhatsAppMediaService;
use Webkul\WhatsApp\Services\WhatsAppService;

class ChatController extends Controller
{
    public function __construct(protected WhatsAppService $whatsAppService) {}

    /**
     * Display WhatsApp chat dashboard or return conversation list via AJAX.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            $messages = WhatsAppMessage::orderBy('created_at', 'desc')->get();

            // Group all messages strictly by 10-digit phone number
            $grouped = $messages->groupBy(function ($msg) {
                $raw = $msg->direction === 'inbound' ? $msg->from_number : $msg->to_number;
                $clean = preg_replace('/\D/', '', (string) $raw);

                return strlen($clean) >= 10 ? substr($clean, -10) : ($clean ?: 'unknown');
            });

            $conversations = $grouped->map(function ($msgs, $phone10) {
                $lastMsg = $msgs->first();
                $firstWithLead = $msgs->firstWhere('lead_id', '!=', null);
                $firstWithPerson = $msgs->firstWhere('person_id', '!=', null);

                $leadId = $firstWithLead?->lead_id;
                $personId = $firstWithPerson?->person_id;

                $person = $personId ? Person::find($personId) : null;
                if (! $person && ! empty($phone10) && $phone10 !== 'unknown') {
                    $person = Person::where('contact_numbers', 'like', "%{$phone10}%")->first();
                }

                $lead = $leadId ? Lead::find($leadId) : null;
                if (! $lead && $person) {
                    $lead = Lead::where('person_id', $person->id)->latest()->first();
                }

                $rawPhone = $lastMsg->direction === 'inbound' ? $lastMsg->from_number : $lastMsg->to_number;
                $displayName = $person?->name ?? $lead?->title ?? $rawPhone;

                return [
                    'phone_number' => $rawPhone,
                    'phone_10' => $phone10,
                    'lead_id' => $lead?->id ?? $leadId,
                    'lead_title' => $lead?->title,
                    'person_id' => $person?->id ?? $personId,
                    'contact_name' => $displayName,
                    'last_message' => $lastMsg?->body ?: ($lastMsg?->type ? '['.ucfirst($lastMsg->type).']' : ''),
                    'last_time' => $lastMsg?->created_at ? $lastMsg->created_at->diffForHumans() : '',
                    'last_status' => $lastMsg?->status,
                    'direction' => $lastMsg?->direction,
                    'total_messages' => $msgs->count(),
                ];
            })->values();

            return response()->json([
                'data' => $conversations,
            ]);
        }

        return view('whatsapp::admin.index');
    }

    /**
     * Get message thread for a specific conversation (by phone number or lead_id).
     */
    public function thread(Request $request, ?int $leadId = null): JsonResponse
    {
        $leadId = $leadId ?: $request->query('lead_id');
        $phone = $request->query('phone');
        $cleanPhone = $phone ? preg_replace('/\D/', '', (string) $phone) : '';
        $phone10 = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;

        $query = WhatsAppMessage::query();

        if ($leadId) {
            $lead = Lead::find($leadId);
            $personPhone10 = null;
            if ($lead && $lead->person && ! empty($lead->person->contact_numbers)) {
                $rawP = $lead->person->contact_numbers[0]['value'] ?? null;
                $cleanP = $rawP ? preg_replace('/\D/', '', (string) $rawP) : '';
                $personPhone10 = strlen($cleanP) >= 10 ? substr($cleanP, -10) : null;
            }

            $query->where(function ($q) use ($leadId, $personPhone10) {
                $q->where('lead_id', $leadId);
                if ($personPhone10) {
                    $q->orWhere('from_number', 'like', "%{$personPhone10}%")
                        ->orWhere('to_number', 'like', "%{$personPhone10}%");
                }
            });
        } elseif (! empty($phone10)) {
            $query->where(function ($q) use ($phone10) {
                $q->where('from_number', 'like', "%{$phone10}%")
                    ->orWhere('to_number', 'like', "%{$phone10}%");
            });
        }

        $messages = $query->orderBy('created_at', 'asc')->get()->map(function ($msg) {
            $data = $msg->toArray();
            if ($msg->media_url || in_array($msg->type, ['image', 'document', 'audio', 'video'], true)) {
                $data['media_stream_url'] = route('admin.whatsapp.media', $msg->id);
            }

            return $data;
        });

        return response()->json([
            'data' => $messages,
        ]);
    }

    /**
     * Stream media file for a WhatsApp message.
     * Automatically self-heals from Meta API if local cached file is missing.
     */
    public function media(int $id, WhatsAppMediaService $mediaService)
    {
        $message = WhatsAppMessage::findOrFail($id);

        // 1. Check if media exists locally on disk across multiple candidate paths
        if (! empty($message->media_url)) {
            $parsedPath = parse_url($message->media_url, PHP_URL_PATH);
            $relativePath = ltrim(preg_replace('#^/storage/#', '', (string) $parsedPath), '/');

            $candidatePaths = [
                Storage::disk('public')->path($relativePath),
                public_path('storage/'.$relativePath),
                storage_path('app/public/'.$relativePath),
                public_path($relativePath),
            ];

            foreach ($candidatePaths as $candidate) {
                if (file_exists($candidate) && is_file($candidate)) {
                    $mime = mime_content_type($candidate) ?: 'application/octet-stream';

                    return response()->file($candidate, [
                        'Content-Type' => $mime,
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
            }
        }

        // 2. Self-healing fallback: If local file is missing, re-fetch from Meta using media_id in raw_payload
        $rawMediaId = data_get($message->raw_payload, "{$message->type}.id")
            ?? data_get($message->raw_payload, 'id');

        if ($rawMediaId && in_array($message->type, ['image', 'video', 'audio', 'document'], true)) {
            $newMediaUrl = $mediaService->fetchAndStore($rawMediaId, $message->type);
            if ($newMediaUrl) {
                $message->update(['media_url' => $newMediaUrl]);

                $parsedPath = parse_url($newMediaUrl, PHP_URL_PATH);
                $relativePath = ltrim(preg_replace('#^/storage/#', '', (string) $parsedPath), '/');
                $candidate = Storage::disk('public')->path($relativePath);

                if (file_exists($candidate) && is_file($candidate)) {
                    $mime = mime_content_type($candidate) ?: 'application/octet-stream';

                    return response()->file($candidate, [
                        'Content-Type' => $mime,
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
            }
        }

        // 3. Fallback: If external media URL exists, redirect
        if (! empty($message->media_url) && filter_var($message->media_url, FILTER_VALIDATE_URL)) {
            return redirect($message->media_url);
        }

        // 4. Return clean placeholder if image was wiped across past server restarts
        if ($message->type === 'image') {
            $name = htmlspecialchars($message->body ?: 'Image');
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200" fill="#f0f2f5"><rect width="300" height="200" rx="12" fill="#e2e8f0"/><path d="M120 85a15 15 0 1 0 0-30 15 15 0 0 0 0 30zm-40 65h160l-45-60-35 45-25-30-55 45z" fill="#94a3b8"/><text x="150" y="170" font-family="sans-serif" font-size="12" font-weight="600" fill="#64748b" text-anchor="middle">'.$name.'</text></svg>';

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'no-cache',
            ]);
        }

        abort(404, 'Media not found');
    }

    /**
     * Send an outbound WhatsApp message (text or template).
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => 'nullable|integer',
            'to' => 'required|string',
            'body' => 'nullable|string',
            'type' => 'nullable|string|in:text,template,image,document,audio,video',
            'template_name' => 'nullable|string',
            'file' => 'nullable|file|max:51200',
        ]);

        $type = $data['type'] ?? 'text';
        $normalizedTo = $this->whatsAppService->normalizeNumber($data['to']);
        $cleanTo = preg_replace('/\D/', '', $normalizedTo);
        $phone10 = strlen($cleanTo) >= 10 ? substr($cleanTo, -10) : $cleanTo;

        $mediaUrl = null;
        $body = $data['body'] ?? '';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $type = 'image';
            } elseif (str_starts_with($mime, 'audio/')) {
                $type = 'audio';
            } elseif (str_starts_with($mime, 'video/')) {
                $type = 'video';
            } else {
                $type = 'document';
            }

            $path = $file->store('whatsapp', 'public');
            $mediaUrl = asset('storage/'.$path);

            $result = $this->whatsAppService->sendMedia(
                $normalizedTo,
                $type,
                $mediaUrl,
                $body ?: $file->getClientOriginalName()
            );

            if (! $body) {
                $body = $file->getClientOriginalName();
            }
        } elseif ($type === 'template') {
            $templateName = $data['template_name'] ?? 'hello_world';
            $result = $this->whatsAppService->sendTemplate($normalizedTo, $templateName);
            $body = 'Template: '.$templateName;
        } else {
            $result = $this->whatsAppService->sendText($normalizedTo, $body);
        }

        $leadId = $data['lead_id'] ?? null;
        $personId = null;

        if (! empty($phone10)) {
            $person = Person::where('contact_numbers', 'like', "%{$phone10}%")->first();
            if ($person) {
                $personId = $person->id;
                if (! $leadId) {
                    $lead = Lead::where('person_id', $person->id)->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', 1);
                    })->latest()->first();
                    $leadId = $lead?->id;
                }
            }
        }

        $message = WhatsAppMessage::create([
            'lead_id' => $leadId,
            'person_id' => $personId,
            'wa_message_id' => data_get($result, 'body.messages.0.id'),
            'direction' => 'outbound',
            'from_number' => config('whatsapp.phone_number_id', 'system'),
            'to_number' => $normalizedTo,
            'type' => $type,
            'body' => $body,
            'media_url' => $mediaUrl,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'raw_payload' => data_get($result, 'body'),
            'sent_at' => now(),
        ]);

        if ($leadId && $result['ok']) {
            $snippet = mb_strimwidth($body ?: ($type.' attachment'), 0, 60, '...');
            $activity = app(ActivityRepository::class)->create([
                'title' => "💬 WhatsApp Message: {$snippet}",
                'type' => 'note',
                'comment' => "<strong>WhatsApp sent to +{$normalizedTo}:</strong><br>".nl2br(e($body ?: "[{$type}]")),
                'additional' => json_encode(['wa_message_id' => $message->wa_message_id, 'to' => $normalizedTo, 'direction' => 'outbound']),
                'schedule_from' => now(),
                'schedule_to' => now(),
                'is_done' => 1,
                'user_id' => auth()->id() ?: 1,
            ]);

            if (method_exists($activity, 'leads')) {
                $activity->leads()->syncWithoutDetaching([$leadId]);
            }

            if ($personId && method_exists($activity, 'persons')) {
                $activity->persons()->syncWithoutDetaching([$personId]);
            }
        }

        return response()->json([
            'ok' => $result['ok'],
            'message' => $message,
        ], $result['ok'] ? 200 : 422);
    }
}
