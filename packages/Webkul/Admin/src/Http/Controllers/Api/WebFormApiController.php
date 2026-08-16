<?php

namespace Webkul\Admin\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;

class WebFormApiController extends Controller
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
     * Ingest web form submissions and perform Omnichannel Identity Resolution.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $rawPhone = (string) $request->input('phone', '');
        $cleanPhone = preg_replace('/\D/', '', $rawPhone);
        $phone10 = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;
        $email = trim((string) $request->input('email', ''));
        $name = trim((string) $request->input('name', '')) ?: ($email ?: ($rawPhone ?: 'Website Prospect'));
        $title = trim((string) $request->input('title', '')) ?: "Website Inquiry - {$name}";
        $message = trim((string) $request->input('message', ''));
        $sourceName = trim((string) $request->input('source', 'Website Form'));

        if (empty($phone10) && empty($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Either phone or email is required to create or match a lead.',
            ], 400);
        }

        // 1. Identity Resolution: Find matching Person by last 10 digits of phone or email
        $person = null;

        if (! empty($phone10)) {
            $person = $this->personRepository->getModel()
                ->where('contact_numbers', 'like', "%{$phone10}%")
                ->first();
        }

        if (! $person && ! empty($email)) {
            $person = $this->personRepository->getModel()
                ->where('emails', 'like', "%{$email}%")
                ->first();
        }

        // 2. Resolve Pipeline & Source
        $pipelineId = $request->input('lead_pipeline_id');
        $pipeline = $pipelineId
            ? $this->pipelineRepository->find($pipelineId)
            : $this->pipelineRepository->getDefaultPipeline();

        $stageId = $pipeline?->stages()->first()?->id;

        $source = $this->sourceRepository->findOneByField('name', $sourceName)
            ?? $this->sourceRepository->findOneByField('name', 'Web Form')
            ?? $this->sourceRepository->first();

        $type = $this->typeRepository->first();

        $isNewLead = false;
        $lead = null;

        // 3. Match or Create Person & Lead
        if ($person) {
            // Find existing open lead for this person (status is 1 or null)
            $lead = $this->leadRepository->getModel()
                ->where('person_id', $person->id)
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 1);
                })
                ->latest()
                ->first();

            if (! $lead) {
                // Create a new lead under this existing person
                Event::dispatch('lead.create.before');

                $lead = $this->leadRepository->create([
                    'entity_type' => 'leads',
                    'title' => $title,
                    'description' => $message,
                    'lead_value' => $request->input('lead_value', 0),
                    'status' => 1,
                    'lead_pipeline_id' => $pipeline?->id,
                    'lead_pipeline_stage_id' => $stageId,
                    'lead_source_id' => $source?->id,
                    'lead_type_id' => $type?->id,
                    'user_id' => $request->input('user_id'),
                    'person_id' => $person->id,
                ]);

                Event::dispatch('lead.create.after', $lead);
                $isNewLead = true;
            }
        } else {
            // Create new Person and new Lead
            Event::dispatch('lead.create.before');

            $personPayload = [
                'name' => $name,
                'emails' => ! empty($email) ? [['value' => $email, 'label' => 'work']] : [],
                'contact_numbers' => ! empty($rawPhone) ? [['value' => $rawPhone, 'label' => 'mobile']] : [],
            ];

            $lead = $this->leadRepository->create([
                'entity_type' => 'leads',
                'title' => $title,
                'description' => $message,
                'lead_value' => $request->input('lead_value', 0),
                'status' => 1,
                'lead_pipeline_id' => $pipeline?->id,
                'lead_pipeline_stage_id' => $stageId,
                'lead_source_id' => $source?->id,
                'lead_type_id' => $type?->id,
                'user_id' => $request->input('user_id'),
                'person' => $personPayload,
            ]);

            $person = $lead->person;
            Event::dispatch('lead.create.after', $lead);
            $isNewLead = true;
        }

        // 4. Record Activity Note under Lead A
        if ($lead) {
            $activityTitle = $isNewLead
                ? "🌐 Web Form Submission: {$title}"
                : "🌐 New Web Form Inquiry: {$title}";

            $activity = $this->activityRepository->create([
                'title' => $activityTitle,
                'type' => 'note',
                'comment' => ! empty($message) ? $message : 'Web form submitted from website.',
                'additional' => json_encode($request->except(['_token'])),
                'schedule_from' => now(),
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
        }

        return response()->json([
            'success' => true,
            'is_new_lead' => $isNewLead,
            'lead_id' => $lead?->id,
            'person_id' => $person?->id,
            'person_name' => $person?->name,
            'pipeline_id' => $lead?->lead_pipeline_id,
            'message' => $isNewLead ? 'New lead created successfully.' : 'Inquiry linked to existing lead.',
        ], 201);
    }
}
