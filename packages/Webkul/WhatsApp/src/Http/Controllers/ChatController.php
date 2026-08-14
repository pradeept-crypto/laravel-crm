<?php

namespace Webkul\WhatsApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\WhatsApp\Models\WhatsAppMessage;
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
            $conversations = WhatsAppMessage::selectRaw("
                CASE 
                    WHEN direction = 'inbound' THEN from_number 
                    ELSE to_number 
                END as contact_phone,
                lead_id,
                person_id,
                MAX(id) as last_message_id,
                MAX(created_at) as last_activity,
                COUNT(*) as total_messages
            ")
                ->whereNotNull('to_number')
                ->where('to_number', '!=', '')
                ->groupByRaw("CASE WHEN direction = 'inbound' THEN from_number ELSE to_number END, lead_id, person_id")
                ->orderByDesc('last_activity')
                ->get()
                ->map(function ($conv) {
                    $lastMsg = WhatsAppMessage::find($conv->last_message_id);
                    $lead = $conv->lead_id ? Lead::find($conv->lead_id) : null;
                    $person = $conv->person_id ? Person::find($conv->person_id) : null;

                    return [
                        'phone_number' => $conv->contact_phone,
                        'lead_id' => $conv->lead_id,
                        'lead_title' => $lead?->title,
                        'person_id' => $conv->person_id,
                        'contact_name' => $person?->name ?? $lead?->title ?? $conv->contact_phone,
                        'last_message' => $lastMsg?->body ?: ($lastMsg?->type ? '['.ucfirst($lastMsg->type).']' : ''),
                        'last_time' => $lastMsg?->created_at ? $lastMsg->created_at->diffForHumans() : '',
                        'last_status' => $lastMsg?->status,
                        'direction' => $lastMsg?->direction,
                    ];
                });

            return response()->json([
                'data' => $conversations,
            ]);
        }

        return view('whatsapp::admin.index');
    }

    /**
     * Get message thread for a specific conversation (by lead_id or phone number).
     */
    public function thread(Request $request, ?int $leadId = null): JsonResponse
    {
        $phone = $request->query('phone');

        $query = WhatsAppMessage::query();

        if ($leadId) {
            $query->where('lead_id', $leadId);
        } elseif ($phone) {
            $query->where(function ($q) use ($phone) {
                $q->where('from_number', $phone)
                    ->orWhere('to_number', $phone);
            });
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        return response()->json([
            'data' => $messages,
        ]);
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
            'type' => 'nullable|string|in:text,template',
            'template_name' => 'nullable|string',
        ]);

        $type = $data['type'] ?? 'text';

        if ($type === 'template') {
            $templateName = $data['template_name'] ?? 'hello_world';
            $result = $this->whatsAppService->sendTemplate($data['to'], $templateName);
            $body = 'Template: '.$templateName;
        } else {
            $body = $data['body'] ?? '';
            $result = $this->whatsAppService->sendText($data['to'], $body);
        }

        $message = WhatsAppMessage::create([
            'lead_id' => $data['lead_id'] ?? null,
            'wa_message_id' => data_get($result, 'body.messages.0.id'),
            'direction' => 'outbound',
            'from_number' => config('whatsapp.phone_number_id', 'system'),
            'to_number' => $data['to'],
            'type' => $type,
            'body' => $body,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'raw_payload' => data_get($result, 'body'),
            'sent_at' => now(),
        ]);

        return response()->json([
            'ok' => $result['ok'],
            'message' => $message,
        ], $result['ok'] ? 200 : 422);
    }
}
