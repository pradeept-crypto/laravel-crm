<?php

use Webkul\Activity\Models\Activity;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\WhatsApp\Jobs\ProcessInboundWhatsAppMessage;
use Webkul\WhatsApp\Models\WhatsAppMessage;

test('web form submission creates new Lead A with Person and Activity Note', function () {
    $pipeline = Pipeline::first();
    $uniquePhone = '98'.rand(10000000, 99999999);

    $response = $this->postJson(route('api.leads.web_form'), [
        'name' => 'Alice Sharma',
        'phone' => $uniquePhone,
        'email' => "alice_{$uniquePhone}@example.com",
        'title' => 'Personal Loan Inquiry',
        'message' => 'Need 5 Lakh loan for 3 years',
        'source' => 'Website Form',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'is_new_lead' => true,
    ]);

    $leadId = $response->json('lead_id');
    $lead = Lead::find($leadId);
    expect($lead)->not->toBeNull();
    expect($lead->title)->toBe('Personal Loan Inquiry');

    $person = $lead->person;
    expect($person)->not->toBeNull();
    expect($person->name)->toBe('Alice Sharma');

    // Assert Activity Note is linked to Lead A
    $activities = $lead->activities;
    expect($activities->count())->toBeGreaterThanOrEqual(1);
    expect($activities->pluck('title')->join(' '))->toContain('Personal Loan Inquiry');
});

test('subsequent web form with same phone links inquiry to existing Lead A without creating duplicate lead', function () {
    $uniquePhone = '91'.rand(10000000, 99999999);

    $response1 = $this->postJson(route('api.leads.web_form'), [
        'name' => 'Bob Singh',
        'phone' => $uniquePhone,
        'email' => "bob_{$uniquePhone}@example.com",
        'title' => 'First Inquiry',
        'message' => 'First message',
    ]);

    $leadId1 = $response1->json('lead_id');

    // Second form with same phone number
    $response2 = $this->postJson(route('api.leads.web_form'), [
        'name' => 'Bob Singh',
        'phone' => $uniquePhone,
        'email' => "bob_{$uniquePhone}@example.com",
        'title' => 'Second Inquiry: Followup',
        'message' => 'Second message with additional details',
    ]);

    $response2->assertStatus(201);
    $response2->assertJson([
        'success' => true,
        'is_new_lead' => false,
        'lead_id' => $leadId1,
    ]);

    $lead = Lead::find($leadId1);
    expect($lead->activities->count())->toBeGreaterThanOrEqual(2);
});

test('voip call webhook logs call activity and recording to matching Lead A', function () {
    $uniquePhone = '99'.rand(10000000, 99999999);

    // 1. Create Lead with phone
    $webFormResponse = $this->postJson(route('api.leads.web_form'), [
        'name' => 'Charlie Patel',
        'phone' => $uniquePhone,
        'email' => "charlie_{$uniquePhone}@example.com",
        'title' => 'Business Loan Inquiry',
    ]);

    $leadId = $webFormResponse->json('lead_id');

    // 2. Simulate VoIP Call webhook
    $callResponse = $this->postJson(route('api.voip.call_log'), [
        'from_number' => "+91 {$uniquePhone}",
        'to_number' => '+91 8012345678',
        'duration' => 210,
        'call_status' => 'completed',
        'direction' => 'inbound',
        'recording_url' => 'https://telephony.example.com/recordings/call_123.mp3',
        'notes' => 'Discussed interest rate and KYC document submission.',
    ]);

    $callResponse->assertStatus(201);
    $callResponse->assertJson([
        'success' => true,
        'lead_id' => $leadId,
    ]);

    $lead = Lead::find($leadId);
    $callActivity = $lead->activities()->where('type', 'call')->first();
    expect($callActivity)->not->toBeNull();
    expect($callActivity->title)->toContain('Inbound Call');
    expect($callActivity->comment)->toContain('call_123.mp3');
});

test('inbound whatsapp message attaches to matching Lead A', function () {
    $uniquePhone = '97'.rand(10000000, 99999999);

    // 1. Create Lead with phone
    $webFormResponse = $this->postJson(route('api.leads.web_form'), [
        'name' => 'Deepak Verma',
        'phone' => $uniquePhone,
        'email' => "deepak_{$uniquePhone}@example.com",
        'title' => 'Mortgage Inquiry',
    ]);

    $leadId = $webFormResponse->json('lead_id');

    // 2. Dispatch Inbound WhatsApp Job
    $message = [
        'id' => 'wamid.omnichannel_test_'.uniqid(),
        'from' => '91'.$uniquePhone,
        'type' => 'text',
        'text' => ['body' => 'Hi, I submitted the form on your website. Please share details.'],
        'timestamp' => time(),
    ];

    $value = [
        'metadata' => ['display_phone_number' => '15550001111'],
        'contacts' => [['profile' => ['name' => 'Deepak Verma']]],
    ];

    $job = new ProcessInboundWhatsAppMessage($message, $value);
    app()->call([$job, 'handle']);

    // 3. Assert WhatsApp Message linked to Lead A
    $waMsg = WhatsAppMessage::where('from_number', '91'.$uniquePhone)->latest()->first();
    expect($waMsg)->not->toBeNull();
    expect($waMsg->lead_id)->toBe($leadId);

    // 4. Assert WhatsApp Activity is also on Lead A timeline
    $lead = Lead::find($leadId);
    $waActivity = $lead->activities()->where('title', 'like', '%WhatsApp Message%')->first();
    expect($waActivity)->not->toBeNull();
    expect($waActivity->comment)->toContain('Hi, I submitted the form');
});
