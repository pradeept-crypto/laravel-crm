<?php

use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

test('lead view page renders successfully with whatsapp tab', function () {
    $user = User::first() ?? User::factory()->create();
    $lead = Lead::first() ?? Lead::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('admin.leads.view', $lead->id))
        ->assertOk()
        ->assertSee('WhatsApp');
});
