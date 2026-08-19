<?php

use Illuminate\Support\Facades\Queue;
use Webkul\WhatsApp\Jobs\ProcessInboundWhatsAppMessage;
use Webkul\WhatsApp\Models\WhatsAppMessage;

beforeEach(function () {
    $this->admin = getDefaultAdmin();
});

it('can verify webhook with matching verify token', function () {
    config(['whatsapp.verify_token' => 'my_secret_token']);

    $response = $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=my_secret_token&hub_challenge=challenge_code_123');

    $response->assertSuccessful()
        ->assertSee('challenge_code_123');
});

it('rejects webhook verification with invalid token', function () {
    config(['whatsapp.verify_token' => 'my_secret_token']);

    $response = $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=challenge_code_123');

    $response->assertStatus(403);
});

it('dispatches job on incoming webhook message payload', function () {
    config(['whatsapp.app_secret' => null]);
    Queue::fake();

    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => '123456',
                'changes' => [
                    [
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => [
                                'display_phone_number' => '15551234567',
                                'phone_number_id' => '100000000000001',
                            ],
                            'contacts' => [
                                [
                                    'profile' => ['name' => 'John Doe'],
                                    'wa_id' => '15559876543',
                                ],
                            ],
                            'messages' => [
                                [
                                    'from' => '15559876543',
                                    'id' => 'wamid.HBgLMTU1NTk4NzY1NDM=',
                                    'timestamp' => '1700000000',
                                    'type' => 'text',
                                    'text' => ['body' => 'Hello from WhatsApp!'],
                                ],
                            ],
                        ],
                        'field' => 'messages',
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhook/whatsapp', $payload);

    $response->assertSuccessful()
        ->assertSee('EVENT_RECEIVED');

    Queue::assertPushed(ProcessInboundWhatsAppMessage::class);
});

it('can access whatsapp admin chat dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.whatsapp.index'))
        ->assertStatus(200);
});

it('can fetch whatsapp conversation list via ajax', function () {
    WhatsAppMessage::create([
        'direction' => 'inbound',
        'from_number' => '15559876543',
        'to_number' => '15551234567',
        'type' => 'text',
        'body' => 'Testing WhatsApp chat',
        'status' => 'received',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.whatsapp.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertSuccessful()
        ->assertJsonStructure(['data']);
});

it('can fetch thread messages for a phone number', function () {
    $msg = WhatsAppMessage::create([
        'direction' => 'inbound',
        'from_number' => '15559876543',
        'to_number' => '15551234567',
        'type' => 'text',
        'body' => 'Conversation thread message',
        'status' => 'received',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.whatsapp.messages', ['phone' => '15559876543']))
        ->assertSuccessful();

    $response->assertJsonFragment([
        'id' => $msg->id,
        'body' => 'Conversation thread message',
    ]);
});
