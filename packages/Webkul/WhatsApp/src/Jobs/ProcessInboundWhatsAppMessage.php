<?php

namespace Webkul\WhatsApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\WhatsApp\Models\WhatsAppMessage;
use Webkul\WhatsApp\Services\WhatsAppMediaService;

class ProcessInboundWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 15;

    /**
     * @param  array  $message  The single message object from Meta's payload (entry.changes.value.messages[n])
     * @param  array  $value  The parent 'value' object, needed for metadata (display number) and contact profile name
     */
    public function __construct(
        protected array $message,
        protected array $value,
    ) {}

    public function handle(
        LeadRepository $leadRepository,
        PersonRepository $personRepository,
        PipelineRepository $pipelineRepository,
        WhatsAppMediaService $mediaService,
    ): void {
        $from = $this->message['from'] ?? null;

        if (! $from) {
            return;
        }

        // Idempotency guard - Meta can redeliver the same webhook event
        // on transient failures, so skip if we've already stored it.
        if (
            isset($this->message['id'])
            && WhatsAppMessage::where('wa_message_id', $this->message['id'])->exists()
        ) {
            return;
        }

        $type = $this->message['type'] ?? 'text';

        $body = match ($type) {
            'text' => data_get($this->message, 'text.body'),
            'button' => data_get($this->message, 'button.text'),
            'interactive' => data_get($this->message, 'interactive.button_reply.title')
                ?? data_get($this->message, 'interactive.list_reply.title'),
            default => null,
        };

        $mediaUrl = null;

        if (in_array($type, ['image', 'video', 'audio', 'document'], true)) {
            $mediaId = data_get($this->message, "{$type}.id");

            if ($mediaId) {
                $mediaUrl = $mediaService->fetchAndStore($mediaId, $type);
            }

            // Documents/images can carry a caption alongside the file.
            $body = $body ?? data_get($this->message, "{$type}.caption");
        }

        $contactName = data_get($this->value, 'contacts.0.profile.name');

        [$leadId, $personId] = $this->resolveContact($leadRepository, $personRepository, $pipelineRepository, $from, $contactName);

        $waMsg = WhatsAppMessage::create([
            'lead_id' => $leadId,
            'person_id' => $personId,
            'wa_message_id' => $this->message['id'] ?? null,
            'direction' => 'inbound',
            'from_number' => $from,
            'to_number' => data_get($this->value, 'metadata.display_phone_number'),
            'type' => $type,
            'body' => $body,
            'media_url' => $mediaUrl,
            'status' => 'received',
            'raw_payload' => $this->message,
            'sent_at' => isset($this->message['timestamp'])
                ? date('Y-m-d H:i:s', (int) $this->message['timestamp'])
                : now(),
        ]);

        if ($leadId) {
            $snippet = mb_strimwidth($body ?: ($type.' attachment'), 0, 60, '...');
            $activity = app(ActivityRepository::class)->create([
                'title' => "💬 WhatsApp Message: {$snippet}",
                'type' => 'note',
                'comment' => "<strong>WhatsApp from +{$from}:</strong><br>".nl2br(e($body ?: "[{$type}]")),
                'additional' => json_encode(['wa_message_id' => $this->message['id'] ?? null, 'from' => $from]),
                'schedule_from' => now(),
                'schedule_to' => now(),
                'is_done' => 1,
                'user_id' => 1,
            ]);

            if (method_exists($activity, 'leads')) {
                $activity->leads()->syncWithoutDetaching([$leadId]);
            }

            if ($personId && method_exists($activity, 'persons')) {
                $activity->persons()->syncWithoutDetaching([$personId]);
            }
        }
    }

    /**
     * @return array{0: int|null, 1: int|null} [leadId, personId]
     */
    protected function resolveContact(
        LeadRepository $leadRepository,
        PersonRepository $personRepository,
        PipelineRepository $pipelineRepository,
        string $waNumber,
        ?string $contactName,
    ): array {
        $person = $personRepository
            ->findWhere([['contact_numbers', 'like', '%'.substr($waNumber, -10).'%']])
            ->first();

        if ($person) {
            $lead = $leadRepository->findOneWhere(['person_id' => $person->id]);

            return [$lead?->id, $person->id];
        }

        if (! config('whatsapp.auto_create_lead')) {
            return [null, null];
        }

        $pipeline = config('whatsapp.default_pipeline_id')
            ? $pipelineRepository->find(config('whatsapp.default_pipeline_id'))
            : $pipelineRepository->getDefaultPipeline();

        $stageId = $pipeline?->stages()->first()?->id;

        $lead = $leadRepository->create([
            'title' => 'WhatsApp Lead - '.($contactName ?: $waNumber),
            'lead_source_id' => $this->resolveSourceId(),
            'lead_pipeline_id' => $pipeline?->id,
            'lead_pipeline_stage_id' => $stageId,
            'person' => [
                'name' => $contactName ?: $waNumber,
                'emails' => [],
                'contact_numbers' => [['value' => $waNumber, 'label' => 'whatsapp']],
            ],
        ]);

        return [$lead->id, $lead->person_id ?? null];
    }

    protected function resolveSourceId(): ?int
    {
        return DB::table('lead_sources')
            ->where('name', 'like', '%WhatsApp%')
            ->value('id') ?? DB::table('lead_sources')->value('id');
    }
}
