<?php

use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\User\Models\User;

test('authenticated user can view lead with pipeline switcher', function () {
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user, 'user');

    $lead = Lead::first();
    if (! $lead) {
        $pipeline = Pipeline::first();
        $stage = $pipeline->stages()->first();
        $lead = Lead::create([
            'title' => 'Test Lead',
            'lead_pipeline_id' => $pipeline->id,
            'lead_pipeline_stage_id' => $stage->id,
            'user_id' => $user->id,
            'entity_type' => 'leads',
        ]);
    }

    $response = $this->get(route('admin.leads.view', $lead->id));
    $response->assertStatus(200);
});

test('authenticated user can switch lead pipeline', function () {
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user, 'user');

    $lead = Lead::first();
    $pipeline = Pipeline::first();
    $stage = $pipeline->stages()->first();

    $response = $this->putJson(route('admin.leads.pipeline.update', $lead->id), [
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['message', 'pipeline', 'stage_id']);

    $lead->refresh();
    expect($lead->lead_pipeline_id)->toBe($pipeline->id);
    expect($lead->lead_pipeline_stage_id)->toBe($stage->id);
});
