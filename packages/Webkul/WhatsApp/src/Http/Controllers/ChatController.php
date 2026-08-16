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
        $normalizedTo = $this->whatsAppService->normalizeNumber($data['to']);
        $cleanTo = preg_replace('/\D/', '', $normalizedTo);
        $phone10 = strlen($cleanTo) >= 10 ? substr($cleanTo, -10) : $cleanTo;

        if ($type === 'template') {
            $templateName = $data['template_name'] ?? 'hello_world';
            $result = $this->whatsAppService->sendTemplate($normalizedTo, $templateName);
            $body = 'Template: '.$templateName;
        } else {
            $body = $data['body'] ?? '';
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
