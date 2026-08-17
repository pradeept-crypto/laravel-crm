<?php

namespace Webkul\Email\InboundEmailProcessor;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;
use Webklex\IMAP\Support\FolderCollection;
use Webklex\PHPIMAP\Message;
use Webkul\Email\Enums\SupportedFolderEnum;
use Webkul\Email\InboundEmailProcessor\Contracts\InboundEmailProcessor;
use Webkul\Email\Repositories\AttachmentRepository;
use Webkul\Email\Repositories\EmailRepository;

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
        $attributes = $message->getAttributes();

        $messageId = $attributes['message_id']->first();

        $email = $this->emailRepository->findOneByField('message_id', $messageId);

        if ($email) {
            return;
        }

        $replyToEmails = $this->getEmailsByAttributeCode($attributes, 'to');

        foreach ($replyToEmails as $to) {
            if ($email = $this->emailRepository->findOneWhere(['message_id' => $to])) {
                break;
            }
        }

        if (! isset($email) && isset($attributes['in_reply_to'])) {
            $inReplyTo = (string) $attributes['in_reply_to']->first();
            $cleanInReplyTo = trim($inReplyTo, '<>');

            $email = $this->emailRepository->findOneWhere(['message_id' => $inReplyTo])
                ?: $this->emailRepository->findOneWhere(['message_id' => $cleanInReplyTo]);

            if (! $email) {
                $email = $this->emailRepository->findOneWhere([['reference_ids', 'like', '%'.$cleanInReplyTo.'%']])
                    ?: $this->emailRepository->findOneWhere([['reference_ids', 'like', '%'.$inReplyTo.'%']]);
            }
        }

        $references = [$messageId];

        if (! isset($email) && isset($attributes['references'])) {
            array_push($references, ...$attributes['references']->all());

            foreach ($references as $reference) {
                $cleanRef = trim((string) $reference, '<>');
                if ($email = $this->emailRepository->findOneWhere([['reference_ids', 'like', '%'.$cleanRef.'%']])
                    ?: $this->emailRepository->findOneWhere([['reference_ids', 'like', '%'.$reference.'%']])) {
                    break;
                }
            }
        }

        /**
         * Maps the folder name to the supported folder in our application.
         *
         * To Do: Review this.
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

        if ($email) {
            $parentEmail = $this->emailRepository->update([
                'folders' => array_unique(array_merge($email->folders, [$folderName])),
                'reference_ids' => array_merge($email->reference_ids ?? [], [$references]),
            ], $email->id);
        }

        $email = $this->emailRepository->create([
            'from' => $attributes['from']->first()->mail,
            'subject' => $attributes['subject']->first(),
            'name' => $attributes['from']->first()->personal,
            'reply' => $message->bodies['html'] ?? $message->bodies['text'],
            'is_read' => (int) $message->flags()->has('seen'),
            'folders' => [$folderName],
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

            if (in_array($folder->name, ['All Mail', '[Gmail]/All Mail', '[Gmail]/Spam', 'Spam'])) {
                return;
            }

            try {
                $messages = $folder->query()->since(now()->subDays(30))->get();

                if ($messages->isEmpty()) {
                    $messages = $folder->query()->all()->limit(25)->get();
                }

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
