<?php

use Webkul\Admin\Http\Resources\LeadResource;
use Webkul\Admin\Http\Resources\StageResource;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

test('currency symbol returns ₹ for INR', function () {
    config(['app.currency' => 'INR']);

    expect(core()->currencySymbol('INR'))->toBe('₹');
    expect(core()->currencySymbol())->toBe('₹');
});

test('formatBasePrice formats amount with ₹ symbol and precision', function () {
    config(['app.currency' => 'INR']);

    expect(core()->formatBasePrice(50000))->toBe('₹50,000.00');
    expect(core()->formatBasePrice(1234567.89))->toBe('₹1,234,567.89');
    expect(core()->formatBasePrice(0))->toBe('₹0.00');
    expect(core()->formatBasePrice(null))->toBe('₹0.00');
});

test('LeadResource and StageResource formatted_lead_value returns amount with ₹ symbol', function () {
    config(['app.currency' => 'INR']);

    $stage = Stage::first();
    if ($stage) {
        $stage->lead_value = 75000;
        $stageResource = (new StageResource($stage))->toArray(request());
        expect($stageResource['formatted_lead_value'])->toBe('₹75,000.00');
    }

    $lead = Lead::first();
    if ($lead) {
        $lead->lead_value = 250000;
        $leadResource = (new LeadResource($lead))->toArray(request());
        expect($leadResource['formatted_lead_value'])->toBe('₹250,000.00');
    }
});

test('admin dashboard layout renders currency meta tag with INR and ₹ symbol', function () {
    config(['app.currency' => 'INR']);

    $user = User::first();
    $response = $this->actingAs($user)->get(route('admin.dashboard.index'));
    $response->assertStatus(200);
    $response->assertSee('INR');
    $response->assertSee('₹');
});
