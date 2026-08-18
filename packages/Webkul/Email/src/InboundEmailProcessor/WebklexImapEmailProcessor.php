<?php

namespace Webkul\Email\InboundEmailProcessor;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;
use Webklex\IMAP\Support\FolderCollection;
use Webklex\PHPIMAP\Message;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Email\Enums\SupportedFolderEnum;
use Webkul\Email\InboundEmailProcessor\Contracts\InboundEmailProcessor;
use Webkul\Email\Repositories\AttachmentRepository;
use Webkul\Email\Repositories\EmailRepository;
use Webkul\Lead\Repositories\LeadRepository;

class WebklexImapEmailProcessor implements InboundEmailProcessor
{
    /**
     * The IMAP client instance.
     */
    protected $client;

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected EmailRepository $emailRepository,
        protected AttachmentRepository $attachmentRepository
    ) {
        try {
            $this->client = Client::make($this->getDefaultConfigs());
        } catch (\Throwable $e) {
            Log::warning('IMAP client instantiation skipped: '.$e->getMessage());
        }
    }

    /**
     * Ensure IMAP client is connected.
     */
    protected function ensureConnected(): void
    {
        try {
            if (! $this->client->isConnected()) {
                $this->client->connect();
            }
        } catch (\Throwable $e) {
            Log::warning('IMAP connection failed: '.$e->getMessage());
        }
    }

    /**
     * Close the connection.
     */
    public function __destruct()
    {
        try {
            if ($this->client && $this->client->isConnected()) {
                $this->client->disconnect();
            }
        } catch (\Throwable) {
            // Safe teardown
        }
    }

    /**
     * Process messages from all folders.
     */
    public function processMessagesFromAllFolders()
    {
        try {
            $this->ensureConnected();

            if (! $this->client->isConnected()) {
                return;
            }

            $rootFolders = $this->client->getFolders();

            $this->processMessagesFromLeafFolders($rootFolders);
        } catch (\Exception $e) {
            Log::error('IMAP Processing Error: '.$e->getMessage());
        }
    }

    /**
     * Process the inbound email.
     *
     * @param  ?Message  $message
     */
    public function processMessage($message = null): void
    {
        if (! $message) {
            return;
        }

        $attributes = $message->getAttributes();

        $rawMessageId = $attributes['message_id']?->first() ?: (string) $message->getMessageId();
        if (empty($rawMessageId)) {
            $rawMessageId = $message->getUid().'@'.(config('mail.domain') ?: 'kaditinnovations.com');
        }
        $messageId = trim((string) $rawMessageId, '<>');

        // Check if message already exists in database
        $existing = $this->emailRepository->findOneWhere(['message_id' => $messageId])
            ?: $this->emailRepository->findOneWhere(['message_id' => '<'.$messageId.'>'])
            ?: $this->emailRepository->findOneWhere(['unique_id' => $messageId]);

        if ($existing) {
            return;
        }

        $fromObj = $attributes['from']?->first() ?: ($message->getFrom() ? $message->getFrom()->first() : null);
        $fromEmail = $fromObj ? (string) ($fromObj->mail ?? '') : '';
        $fromName = $fromObj ? (string) ($fromObj->personal ?: $fromEmail) : '';

        $rawSubject = $attributes['subject']?->first() ?: (string) $message->getSubject();
        $subject = (string) ($rawSubject ?: 'No Subject');

        $htmlBody = $message->getHTMLBody() ?: ($message->bodies['html'] ?? '');
        $textBody = $message->getTextBody() ?: ($message->bodies['text'] ?? '');
        $body = ! empty($htmlBody) ? $htmlBody : (! empty($textBody) ? $textBody : '');

        // Find parent email by in_reply_to or references
        $email = null;
        if (isset($attributes['in_reply_to'])) {
            $inReplyTo = (string) $attributes['in_reply_to']->first();
            $cleanInReplyTo = trim($inReplyTo, '<>');

            $email = $this->emailRepository->findOneWhere(['message_id' => $cleanInReplyTo])
                ?: $this->emailRepository->findOneWhere(['message_id' => '<'.$cleanInReplyTo.'>'])
                ?: $this->emailRepository->findOneWhere([['reference_ids', 'like', '%'.$cleanInReplyTo.'%']]);
        }

        $references = [$messageId];

        if (! $email && isset($attributes['references'])) {
            array_push($references, ...$attributes['references']->all());

            foreach ($references as $reference) {
                $cleanRef = trim((string) $reference, '<>');
                if ($email = $this->emailRepository->findOneWhere(['message_id' => $cleanRef])
                    ?: $this->emailRepository->findOneWhere(['message_id' => '<'.$cleanRef.'>'])
                    ?: $this->emailRepository->findOneWhere([['reference_ids', 'like', '%'.$cleanRef.'%']])) {
                    break;
                }
            }
        }

        // If not found yet, match by base subject (e.g. "Re: test" -> "test")
        if (! $email && str_starts_with(strtolower($subject), 're:')) {
            $baseSubject = trim(substr($subject, 3));
            $email = $this->emailRepository->findOneWhere([
                'subject' => $baseSubject,
            ]);
        }

        /**
         * Maps the folder name to the supported folder in our application.
         */
        $rawFolderName = strtolower((string) $message->getFolder()->name);
        $folderName = match (true) {
            str_contains($rawFolderName, 'inbox') => SupportedFolderEnum::INBOX->value,
            str_contains($rawFolderName, 'important') => SupportedFolderEnum::IMPORTANT->value,
            str_contains($rawFolderName, 'starred') => SupportedFolderEnum::STARRED->value,
            str_contains($rawFolderName, 'draft') => SupportedFolderEnum::DRAFT->value,
            str_contains($rawFolderName, 'sent') => SupportedFolderEnum::SENT->value,
            str_contains($rawFolderName, 'trash') || str_contains($rawFolderName, 'bin') => SupportedFolderEnum::TRASH->value,
            default => SupportedFolderEnum::INBOX->value,
        };

        $parentEmail = null;
        $leadId = null;

        if ($email) {
            $existingFolders = is_array($email->folders) ? $email->folders : (json_decode($email->folders, true) ?: []);
            $parentEmail = $this->emailRepository->update([
                'folders' => array_values(array_unique(array_merge($existingFolders, [SupportedFolderEnum::INBOX->value]))),
                'reference_ids' => array_values(array_unique(array_merge($email->reference_ids ?? [], $references))),
            ], $email->id);

            $leadId = $parentEmail?->lead_id;
        }

        // If leadId is still not found, search Contact Person by email
        if (! $leadId && ! empty($fromEmail)) {
            $person = app(PersonRepository::class)->findOneWhere([
                ['emails', 'like', '%"'.$fromEmail.'"%'],
            ]) ?: app(PersonRepository::class)->findOneWhere([
                ['emails', 'like', '%'.$fromEmail.'%'],
            ]);

            if ($person) {
                $lead = app(LeadRepository::class)->findOneWhere([
                    'person_id' => $person->id,
                ]);
                $leadId = $lead?->id;
            }
        }

        $email = $this->emailRepository->create([
            'from' => $fromEmail,
            'subject' => $subject,
            'name' => $fromName,
            'reply' => $body,
            'is_read' => (int) $message->flags()->has('seen'),
            'folders' => array_values(array_unique([$folderName, SupportedFolderEnum::INBOX->value])),
            'reply_to' => $this->getEmailsByAttributeCode($attributes, 'to'),
            'cc' => $this->getEmailsByAttributeCode($attributes, 'cc'),
            'bcc' => $this->getEmailsByAttributeCode($attributes, 'bcc'),
            'source' => 'email',
            'user_type' => 'person',
            'unique_id' => $messageId,
            'message_id' => $messageId,
            'reference_ids' => $references,
            'created_at' => $this->convertToDesiredTimezone($message->date->toDate()),
            'parent_id' => $parentEmail?->id,
            'lead_id' => $leadId,
        ]);

        if ($message->hasAttachments()) {
            $this->attachmentRepository->uploadAttachments($email, [
                'source' => 'email',
                'attachments' => $message->getAttachments(),
            ]);
        }
    }

    /**
     * Process the messages from all folders.
     *
     * @param  FolderCollection  $rootFoldersCollection
     */
    protected function processMessagesFromLeafFolders($rootFoldersCollection = null): void
    {
        $rootFoldersCollection->each(function ($folder) {
            if (! $folder->children->isEmpty()) {
                $this->processMessagesFromLeafFolders($folder->children);

                return;
            }

            $folderLower = strtolower((string) $folder->name);
            if (str_contains($folderLower, 'all mail') || str_contains($folderLower, 'spam') || str_contains($folderLower, 'trash') || str_contains($folderLower, 'bin')) {
                return;
            }

            try {
                // Fetch the newest 50 messages from each folder in descending order
                $messages = $folder->query()
                    ->setFetchOrderDesc()
                    ->setFetchBody(true)
                    ->limit(50)
                    ->get();

                $messages->each(function ($message) {
                    try {
                        $this->processMessage($message);
                    } catch (\Throwable $e) {
                        Log::warning('Error processing IMAP message: '.$e->getMessage());
                    }
                });
            } catch (\Throwable $e) {
                Log::warning('Error fetching IMAP folder '.$folder->name.': '.$e->getMessage());
            }
        });
    }

    /**
     * Get the emails by the attribute code.
     */
    protected function getEmailsByAttributeCode(array $attributes, string $attributeCode): array
    {
        $emails = [];

        if (isset($attributes[$attributeCode])) {
            $emails = collect($attributes[$attributeCode]->all())->map(fn ($attribute) => $attribute->mail)->toArray();
        }

        return $emails;
    }

    /**
     * Convert the date to the desired timezone.
     *
     * @param  Carbon  $carbonDate
     * @param  ?string  $targetTimezone
     */
    protected function convertToDesiredTimezone($carbonDate, $targetTimezone = null)
    {
        $targetTimezone = $targetTimezone ?: config('app.timezone');

        return $carbonDate->clone()->setTimezone($targetTimezone);
    }

    /**
     * Get the default configurations.
     */
    protected function getDefaultConfigs(): array
    {
        $defaultConfig = config('imap.accounts.default') ?: [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'protocol' => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => true,
            'username' => '',
            'password' => '',
            'authentication' => null,
        ];

        try {
            $defaultConfig['host'] = core()->getConfigData('email.imap.account.host') ?: ($defaultConfig['host'] ?? 'imap.gmail.com');
            $defaultConfig['port'] = core()->getConfigData('email.imap.account.port') ?: ($defaultConfig['port'] ?? 993);
            $defaultConfig['encryption'] = core()->getConfigData('email.imap.account.encryption') ?: ($defaultConfig['encryption'] ?? 'ssl');
            $defaultConfig['validate_cert'] = (bool) (core()->getConfigData('email.imap.account.validate_cert') ?? true);
            $defaultConfig['username'] = core()->getConfigData('email.imap.account.username') ?: ($defaultConfig['username'] ?? '');
            $defaultConfig['password'] = core()->getConfigData('email.imap.account.password') ?: ($defaultConfig['password'] ?? '');
        } catch (\Throwable) {
            // Safe fallback if database is loading
        }

        return $defaultConfig;
    }
}
