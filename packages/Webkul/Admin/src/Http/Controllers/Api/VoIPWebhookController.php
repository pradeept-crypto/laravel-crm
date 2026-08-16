<?php

namespace Webkul\Admin\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;

class VoIPWebhookController extends Controller
{
    public function __construct(
        protected LeadRepository $leadRepository,
        protected PersonRepository $personRepository,
        protected PipelineRepository $pipelineRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
        protected ActivityRepository $activityRepository,
    ) {}

    /**
     * Ingest VoIP call logs and link to matching Lead.
     */
    public function logCall(Request $request): JsonResponse
    {
        Log::info('VoIP Webhook Received', ['payload' => $request->all()]);

        $callerNumber = (string) (
            $request->input('from_number')
            ?? $request->input('from')
            ?? $request->input('From')
            ?? $request->input('Caller')
            ?? $request->input('caller_id')
            ?? ''
        );

        $calleeNumber = (string) (
            $request->input('to_number')
            ?? $request->input('to')
            ?? $request->input('To')
            ?? ''
        );

        $durationSeconds = (int) (
            $request->input('duration')
            ?? $request->input('CallDuration')
            ?? $request->input('DialCallDuration')
            ?? 0
        );

        $status = strtolower((string) (
            $request->input('call_status')
            ?? $request->input('CallStatus')
            ?? $request->input('status')
            ?? 'completed'
        ));

        $direction = strtolower((string) (
            $request->input('direction')
            ?? 'inbound'
        ));

        $recordingUrl = (string) (
            $request->input('recording_url')
            ?? $request->input('RecordingUrl')
            ?? ''
        );

        $notes = trim((string) (
            $request->input('notes')
            ?? $request->input('comment')
            ?? ''
        ));

        $targetNumber = ($direction === 'outbound' && ! empty($calleeNumber)) ? $calleeNumber : $callerNumber;
        $cleanPhone = preg_replace('/\D/', '', $targetNumber);
        $phone10 = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;

        if (empty($phone10)) {
            return response()->json([
                'success' => false,
                'message' => 'Caller phone number is required.',
            ], 400);
        }

        // 1. Identity Resolution: Find Person by phone
        $person = $this->personRepository->getModel()
            ->where('contact_numbers', 'like', "%{$phone10}%")
            ->first();

        $lead = null;

        if ($person) {
            $lead = $this->leadRepository->getModel()
                ->where('person_id', $person->id)
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 1);
                })
                ->latest()
                ->first();
        }

        // 2. If no Lead exists, auto-create Lead
        if (! $lead) {
            $pipeline = $this->pipelineRepository->getDefaultPipeline();
            $stageId = $pipeline?->stages()->first()?->id;
            $source = $this->sourceRepository->findOneByField('name', 'Phone Call')
                ?? $this->sourceRepository->first();
            $type = $this->typeRepository->first();

            Event::dispatch('lead.create.before');

            $personName = $person ? $person->name : "Caller ({$targetNumber})";

            if (! $person) {
                $lead = $this->leadRepository->create([
                    'entity_type' => 'leads',
                    'title' => "Inbound Call - {$personName}",
                    'lead_value' => 0,
                    'status' => 1,
                    'lead_pipeline_id' => $pipeline?->id,
                    'lead_pipeline_stage_id' => $stageId,
                    'lead_source_id' => $source?->id,
                    'lead_type_id' => $type?->id,
                    'person' => [
                        'name' => $personName,
                        'emails' => [],
                        'contact_numbers' => [['value' => $targetNumber, 'label' => 'mobile']],
                    ],
                ]);
                $person = $lead->person;
            } else {
                $lead = $this->leadRepository->create([
                    'entity_type' => 'leads',
                    'title' => "Inbound Call - {$personName}",
                    'lead_value' => 0,
                    'status' => 1,
                    'lead_pipeline_id' => $pipeline?->id,
                    'lead_pipeline_stage_id' => $stageId,
                    'lead_source_id' => $source?->id,
                    'lead_type_id' => $type?->id,
                    'person_id' => $person->id,
                ]);
            }

            Event::dispatch('lead.create.after', $lead);
        }

        // 3. Format Duration & Comment
        $minutes = floor($durationSeconds / 60);
        $seconds = $durationSeconds % 60;
        $formattedDuration = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";

        $directionLabel = ucfirst($direction);
        $statusLabel = ucfirst($status);
        $activityTitle = "📞 {$directionLabel} Call ({$statusLabel} - {$formattedDuration})";

        $commentParts = [];
        if (! empty($notes)) {
            $commentParts[] = '<strong>Notes:</strong> '.htmlspecialchars($notes);
        }
        $commentParts[] = "<strong>From:</strong> {$callerNumber} &nbsp;|&nbsp; <strong>To:</strong> {$calleeNumber}";
        $commentParts[] = "<strong>Duration:</strong> {$formattedDuration} &nbsp;|&nbsp; <strong>Status:</strong> {$statusLabel}";

        if (! empty($recordingUrl)) {
            $commentParts[] = "<div class='mt-2'><a href='{$recordingUrl}' target='_blank' class='text-blue-600 underline font-semibold'>▶️ Listen / Download Call Recording</a></div>";
        }

        $commentHtml = implode('<br>', $commentParts);

        // 4. Record Call Activity
        $activity = $this->activityRepository->create([
            'title' => $activityTitle,
            'type' => 'call',
            'comment' => $commentHtml,
            'additional' => json_encode([
                'direction' => $direction,
                'status' => $status,
                'duration' => $durationSeconds,
                'recording_url' => $recordingUrl,
                'caller_number' => $callerNumber,
                'callee_number' => $calleeNumber,
            ]),
            'schedule_from' => now()->subSeconds($durationSeconds),
            'schedule_to' => now(),
            'is_done' => 1,
            'user_id' => $lead->user_id ?? 1,
        ]);

        if (method_exists($activity, 'leads')) {
            $activity->leads()->syncWithoutDetaching([$lead->id]);
        }

        if ($person && method_exists($activity, 'persons')) {
            $activity->persons()->syncWithoutDetaching([$person->id]);
        }

        $lead->touch();

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'person_id' => $person?->id,
            'activity_id' => $activity->id,
            'title' => $activityTitle,
            'message' => 'VoIP call logged to Lead successfully.',
        ], 201);
    }
}
