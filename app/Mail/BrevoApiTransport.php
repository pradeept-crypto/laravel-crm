<?php

namespace App\Mail;

use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class BrevoApiTransport extends AbstractTransport
{
    /**
     * Send email via Brevo REST API over HTTPS (Port 443).
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $apiKey = getenv('BREVO_API_KEY') ?: (getenv('MAIL_PASSWORD') ?: (env('BREVO_API_KEY') ?: env('MAIL_PASSWORD')));

        $to = [];
        foreach ($email->getTo() as $address) {
            $to[] = array_filter([
                'email' => $address->getAddress(),
                'name' => $address->getName() ?: null,
            ]);
        }

        $fromList = $email->getFrom();
        $fromObj = ! empty($fromList) ? $fromList[0] : null;

        $senderEmail = $fromObj ? $fromObj->getAddress() : (config('mail.from.address') ?: 'pradeep.t@kaditinnovations.com');
        $senderName = $fromObj && $fromObj->getName() ? $fromObj->getName() : (config('mail.from.name') ?: 'AUURA CRM');

        $htmlContent = (string) ($email->getHtmlBody() ?: nl2br((string) $email->getTextBody() ?: 'New Message from AUURA CRM'));

        $payload = [
            'sender' => ['email' => $senderEmail, 'name' => $senderName],
            'to' => $to,
            'subject' => $email->getSubject() ?: 'New Message from AUURA CRM',
            'htmlContent' => $htmlContent,
        ];

        if ($email->getTextBody()) {
            $payload['textContent'] = (string) $email->getTextBody();
        }

        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'name' => $attachment->getFilename(),
                'content' => base64_encode($attachment->getBody()),
            ];
        }

        if (! empty($attachments)) {
            $payload['attachment'] = $attachments;
        }

        $response = Http::timeout(10)->withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (! $response->successful()) {
            throw new Exception('Brevo API Error ('.$response->status().'): '.$response->body());
        }
    }

    public function __toString(): string
    {
        return 'brevo-api';
    }
}
