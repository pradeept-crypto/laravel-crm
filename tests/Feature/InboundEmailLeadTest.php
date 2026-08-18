<?php

use Illuminate\Support\Str;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\FlagCollection;
use Webkul\Contact\Models\Person;
use Webkul\Email\InboundEmailProcessor\WebklexImapEmailProcessor;
use Webkul\Email\Models\Email;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;

function createMockEmailMessage(string $fromEmail, string $fromName, string $subject, string $body, ?string $inReplyTo = null, ?string $messageId = null)
{
    $msgId = $messageId ?: Str::random(16).'@kaditinnovations.com';

    $message = Mockery::mock(Message::class);

    $fromAttr = new Attribute('from', [
        (object) [
            'mail' => $fromEmail,
            'personal' => $fromName,
        ],
    ]);

    $toAttr = new Attribute('to', [
        (object) [
            'mail' => 'pradeep.t@kaditinnovations.com',
            'personal' => 'AUURA CRM',
        ],
    ]);

    $subjectAttr = new Attribute('subject', [$subject]);
    $msgIdAttr = new Attribute('message_id', [$msgId]);

    $attributes = [
        'from' => $fromAttr,
        'to' => $toAttr,
        'subject' => $subjectAttr,
        'message_id' => $msgIdAttr,
    ];

    if ($inReplyTo) {
        $attributes['in_reply_to'] = new Attribute('in_reply_to', [$inReplyTo]);
    }

    $message->shouldReceive('getAttributes')->andReturn($attributes);
    $message->shouldReceive('getMessageId')->andReturn($msgId);
    $message->shouldReceive('getUid')->andReturn(rand(1000, 9999));
    $message->shouldReceive('getFrom')->andReturn($fromAttr);
    $message->shouldReceive('getTo')->andReturn($toAttr);
    $message->shouldReceive('getSubject')->andReturn($subject);
    $message->shouldReceive('getHTMLBody')->andReturn($body);
    $message->shouldReceive('getTextBody')->andReturn(strip_tags($body));

    if ($inReplyTo) {
        $message->shouldReceive('getInReplyTo')->andReturn(new Attribute('in_reply_to', [$inReplyTo]));
    } else {
        $message->shouldReceive('getInReplyTo')->andReturn(null);
    }

    $flags = Mockery::mock(FlagCollection::class);
    $flags->shouldReceive('has')->with('seen')->andReturn(false);
    $message->shouldReceive('flags')->andReturn($flags);

    $folder = Mockery::mock(Folder::class);
    $folder->name = 'INBOX';
    $message->shouldReceive('getFolder')->andReturn($folder);

    $message->shouldReceive('getDate')->andReturn(new Attribute('date', [now()]));
    $message->shouldReceive('hasAttachments')->andReturn(false);
    $message->bodies = ['html' => $body, 'text' => strip_tags($body)];

    return $message;
}

test('new incoming email from unknown sender creates a new lead in Enquiry pipeline and attaches email', function () {
    $pipeline = Pipeline::firstOrCreate(
        ['name' => 'Enquiry'],
        ['is_default' => 1]
    );

    $randomStr = Str::random(8);
    $senderEmail = "client_{$randomStr}@example.com";
    $senderName = "Client {$randomStr}";
    $subject = "Inquiry for Personal Loan {$randomStr}";
    $body = 'Hello, I am interested in a loan.';

    $message = createMockEmailMessage($senderEmail, $senderName, $subject, $body);

    $processor = app(WebklexImapEmailProcessor::class);
    $processor->processMessage($message);

    // 1. Verify email exists in DB with inbox folder
    $email = Email::where('from', 'like', '%'.$senderEmail.'%')->first();
    expect($email)->not->toBeNull();
    expect($email->subject)->toBe($subject);
    expect($email->folders)->toContain('inbox');

    // 2. Verify Lead was automatically created in Enquiry pipeline
    $lead = Lead::find($email->lead_id);
    expect($lead)->not->toBeNull();
    expect($lead->lead_pipeline_id)->toBe($pipeline->id);
    expect($lead->title)->toBe($subject);

    // 3. Verify Person was created with correct name and email
    $person = $lead->person;
    expect($person)->not->toBeNull();
    expect($person->name)->toBe($senderName);
    expect(json_encode($person->emails))->toContain($senderEmail);
});

test('subsequent email from existing sender does not create another lead and attaches to existing lead', function () {
    $pipeline = Pipeline::firstOrCreate(
        ['name' => 'Enquiry'],
        ['is_default' => 1]
    );

    $randomStr = Str::random(8);
    $senderEmail = "existing_{$randomStr}@example.com";
    $senderName = "Existing Client {$randomStr}";

    // First email
    $msg1 = createMockEmailMessage($senderEmail, $senderName, "First Email {$randomStr}", 'First message body');
    $processor = app(WebklexImapEmailProcessor::class);
    $processor->processMessage($msg1);

    $leadCountBefore = Lead::whereHas('person', function ($q) use ($senderEmail) {
        $q->where('emails', 'like', '%'.$senderEmail.'%');
    })->count();
    expect($leadCountBefore)->toBe(1);

    $firstLead = Lead::whereHas('person', function ($q) use ($senderEmail) {
        $q->where('emails', 'like', '%'.$senderEmail.'%');
    })->first();

    // Second email from same sender
    $msg2 = createMockEmailMessage($senderEmail, $senderName, "Second Email {$randomStr}", 'Second message body');
    $processor->processMessage($msg2);

    // Assert NO duplicate lead was created
    $leadCountAfter = Lead::whereHas('person', function ($q) use ($senderEmail) {
        $q->where('emails', 'like', '%'.$senderEmail.'%');
    })->count();
    expect($leadCountAfter)->toBe(1);

    // Assert second email is attached to the same existing lead
    $emails = Email::where('lead_id', $firstLead->id)->get();
    expect($emails->count())->toBe(2);
});

test('multiple rapid emails from same sender creates exactly one lead', function () {
    $randomStr = Str::random(8);
    $senderEmail = "rapid_{$randomStr}@example.com";
    $senderName = "Rapid Client {$randomStr}";

    $processor = app(WebklexImapEmailProcessor::class);

    for ($i = 1; $i <= 3; $i++) {
        $msg = createMockEmailMessage($senderEmail, $senderName, "Message {$i} {$randomStr}", "Body {$i}");
        $processor->processMessage($msg);
    }

    $leadCount = Lead::whereHas('person', function ($q) use ($senderEmail) {
        $q->where('emails', 'like', '%'.$senderEmail.'%');
    })->count();
    expect($leadCount)->toBe(1);

    $lead = Lead::whereHas('person', function ($q) use ($senderEmail) {
        $q->where('emails', 'like', '%'.$senderEmail.'%');
    })->first();

    $emails = Email::where('lead_id', $lead->id)->get();
    expect($emails->count())->toBe(3);
});

test('sender without display name uses email as fallback for lead name', function () {
    $randomStr = Str::random(8);
    $senderEmail = "nodisplay_{$randomStr}@example.com";

    $msg = createMockEmailMessage($senderEmail, '', "No Display Subject {$randomStr}", 'Some content');
    $processor = app(WebklexImapEmailProcessor::class);
    $processor->processMessage($msg);

    $lead = Lead::whereHas('person', function ($q) use ($senderEmail) {
        $q->where('emails', 'like', '%'.$senderEmail.'%');
    })->first();

    expect($lead)->not->toBeNull();
    expect($lead->person->name)->toBe($senderEmail);
});

test('incoming email reply with in_reply_to header appends to existing parent email and lead', function () {
    $pipeline = Pipeline::firstOrCreate(['name' => 'Enquiry'], ['is_default' => 1]);
    $randomStr = Str::random(8);
    $senderEmail = "threaded_{$randomStr}@example.com";
    $parentMsgId = "parent_{$randomStr}@kaditinnovations.com";

    // 1. Initial inbound email creates lead
    $msg1 = createMockEmailMessage($senderEmail, "Thread User {$randomStr}", "Original Subject {$randomStr}", 'Initial message', null, $parentMsgId);
    $processor = app(WebklexImapEmailProcessor::class);
    $processor->processMessage($msg1);

    $parentEmail = Email::where('message_id', $parentMsgId)->first();
    expect($parentEmail)->not->toBeNull();

    // 2. Incoming reply referencing parentMsgId
    $replyMsgId = "reply_{$randomStr}@kaditinnovations.com";
    $msg2 = createMockEmailMessage($senderEmail, "Thread User {$randomStr}", "Re: Original Subject {$randomStr}", 'Reply message', $parentMsgId, $replyMsgId);
    $processor->processMessage($msg2);

    $replyEmail = Email::where('message_id', $replyMsgId)->first();
    expect($replyEmail)->not->toBeNull();
    expect($replyEmail->parent_id)->toBe($parentEmail->id);
    expect($replyEmail->lead_id)->toBe($parentEmail->lead_id);
});
