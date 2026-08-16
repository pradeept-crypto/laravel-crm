<?php

namespace Webkul\WebForm\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\WebForm\Http\Requests\WebForm;
use Webkul\WebForm\Repositories\WebFormRepository;

class WebFormController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected WebFormRepository $webFormRepository,
        protected PersonRepository $personRepository,
        protected LeadRepository $leadRepository,
        protected PipelineRepository $pipelineRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
    ) {}

    /**
     * Remove the specified email template from storage.
     */
    public function formJS(string $formId): Response
    {
        $webForm = $this->webFormRepository->findOneByField('form_id', $formId);

        return response()->view('web_form::settings.web-forms.embed', compact('webForm'))
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * Remove the specified email template from storage.
     */
    public function formStore(int $id): JsonResponse
    {
        $rawEmail = request('persons.emails.0.value') ?: request('persons.emails.value');
        $rawPhone = request('persons.contact_numbers.0.value') ?: request('persons.contact_numbers.value');
        $cleanPhone = $rawPhone ? preg_replace('/\D/', '', (string) $rawPhone) : '';
        $phone10 = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;

        $person = null;

        if (! empty($rawEmail)) {
            $person = $this->personRepository
                ->getModel()
                ->where('emails', 'like', '%'.$rawEmail.'%')
                ->first();
        }

        if (! $person && ! empty($phone10)) {
            $person = $this->personRepository
                ->getModel()
                ->where('contact_numbers', 'like', '%'.$phone10.'%')
                ->first();
        }

        if ($person) {
            // Update email or phone if missing
            $existingEmails = collect($person->emails ?? []);
            if (! empty($rawEmail) && ! $existingEmails->pluck('value')->contains($rawEmail)) {
                $emails = $person->emails ?? [];
                $emails[] = ['value' => $rawEmail, 'label' => 'work'];
                $this->personRepository->update(['emails' => $emails, 'entity_type' => 'persons'], $person->id);
            }

            $existingPhones = collect($person->contact_numbers ?? []);
            if (! empty($rawPhone) && ! $existingPhones->pluck('value')->contains($rawPhone)) {
                $numbers = $person->contact_numbers ?? [];
                $numbers[] = ['value' => $rawPhone, 'label' => 'work'];
                $this->personRepository->update(['contact_numbers' => $numbers, 'entity_type' => 'persons'], $person->id);
            }

            request()->request->add(['persons' => array_merge(request('persons') ?? [], ['id' => $person->id])]);
        }

        app(WebForm::class);

        $webForm = $this->webFormRepository->findOrFail($id);

        if ($webForm->create_lead) {
            // Check if this person already has an active open lead (status is 1 or null)
            $existingLead = $person
                ? $this->leadRepository->getModel()
                    ->where('person_id', $person->id)
                    ->where(function ($query) {
                        $query->whereNull('status')->orWhere('status', 1);
                    })
                    ->latest()
                    ->first()
                : null;

            if ($existingLead) {
                // Log submission as Activity Note on existing Lead A
                $leadTitle = request('leads.title') ?: ($webForm->title ?: 'Web Form Submission');
                $formPayload = request('leads') ?? [];

                $attributes = $this->attributeRepository->findWhere(['entity_type' => 'leads'])->keyBy('code');

                $commentLines = ['<strong>New Web Form Submission ('.e($webForm->title ?: 'Web Form').'):</strong>'];

                foreach ($formPayload as $k => $v) {
                    if (is_scalar($v) && ! empty($v) && ! in_array($k, ['entity_type', 'status', 'person'])) {
                        $attribute = $attributes->get($k);
                        $fieldLabel = $attribute ? $attribute->name : ucfirst(str_replace('_', ' ', $k));
                        $displayVal = (string) $v;

                        if ($attribute && in_array($attribute->type, ['select', 'lookup', 'multiselect', 'checkbox']) && is_numeric($v)) {
                            $option = AttributeOption::find($v);
                            if ($option) {
                                $displayVal = $option->name;
                            }
                        }

                        $commentLines[] = '<strong>'.e($fieldLabel).':</strong> '.e($displayVal);
                    }
                }

                $activity = app(ActivityRepository::class)->create([
                    'title' => "🌐 Web Form Submission: {$leadTitle}",
                    'type' => 'note',
                    'comment' => implode('<br>', $commentLines),
                    'additional' => json_encode(request()->all()),
                    'schedule_from' => now(),
                    'schedule_to' => now(),
                    'is_done' => 1,
                    'user_id' => $existingLead->user_id ?? 1,
                ]);

                if (method_exists($activity, 'leads')) {
                    $activity->leads()->syncWithoutDetaching([$existingLead->id]);
                }
                if (method_exists($activity, 'persons')) {
                    $activity->persons()->syncWithoutDetaching([$person->id]);
                }

                $existingLead->touch();
            } else {
                request()->request->add(['entity_type' => 'leads']);

                Event::dispatch('lead.create.before');

                $data = request('leads');

                $data['entity_type'] = 'leads';

                $data['person'] = request('persons');

                $data['status'] = 1;

                $pipeline = $webForm->lead_pipeline_id
                    ? $this->pipelineRepository->find($webForm->lead_pipeline_id)
                    : null;

                if (! $pipeline) {
                    $pipeline = $this->pipelineRepository->getDefaultPipeline();
                }

                $stage = $pipeline->stages()->first();

                $data['lead_pipeline_id'] = $pipeline->id;

                $data['lead_pipeline_stage_id'] = $stage->id;

                $data['title'] = request('leads.title') ?: ($webForm->title ?: 'Lead From Web Form');

                $data['lead_value'] = request('leads.lead_value') ?: 0;

                if (! request('leads.lead_source_id')) {
                    $source = $this->sourceRepository->findOneByField('name', 'Web Form');

                    if (! $source) {
                        $source = $this->sourceRepository->first();
                    }

                    $data['lead_source_id'] = $source->id;
                }

                $data['lead_type_id'] = request('leads.lead_type_id') ?: $this->typeRepository->first()->id;

                $lead = $this->leadRepository->create($data);

                Event::dispatch('lead.create.after', $lead);
            }
        } else {
            if (! $person) {
                Event::dispatch('contacts.person.create.before');

                $data = request('persons');

                request()->request->add(['entity_type' => 'persons']);

                $data['entity_type'] = 'persons';

                $person = $this->personRepository->create($data);

                Event::dispatch('contacts.person.create.after', $person);
            }
        }

        if ($webForm->submit_success_action == 'message') {
            return response()->json([
                'message' => $webForm->submit_success_content,
            ], 200);
        } else {
            return response()->json([
                'redirect' => $webForm->submit_success_content,
            ], 301);
        }
    }

    /**
     * Remove the specified email template from storage.
     */
    public function preview(string $id): View
    {
        $webForm = $this->webFormRepository->findOneByField('form_id', $id);

        if (is_null($webForm)) {
            abort(404);
        }

        return view('web_form::settings.web-forms.preview', compact('webForm'));
    }

    /**
     * Preview the web form from datagrid.
     */
    public function view(int $id): View
    {
        $webForm = $this->webFormRepository->findOneByField('id', $id);

        request()->merge(['id' => $webForm->form_id]);

        if (is_null($webForm)) {
            abort(404);
        }

        return view('web_form::settings.web-forms.preview', compact('webForm'));
    }
}
