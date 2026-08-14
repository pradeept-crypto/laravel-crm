<?php

use TestModule\Models\TestModule;

beforeEach(function () {
    $this->admin = getDefaultAdmin();
});

it('can access test module dashboard page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.testmodule.dashboard.index'))
        ->assertSuccessful()
        ->assertSee('Test Module Dashboard');
});

it('can fetch test module dashboard stats json', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.testmodule.dashboard.stats'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'total_hotels',
            'total_cities',
            'hotels_by_city',
            'recent_hotels',
        ]);
});

it('can access test module records page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.testmodule.records.index'))
        ->assertSuccessful()
        ->assertSee('Hotel Records');
});

it('can fetch records datagrid via ajax', function () {
    TestModule::create([
        'hotel_name' => 'Grand Palace Hotel',
        'contact_number' => '+1-555-0199',
        'email' => 'info@grandpalace.com',
        'city' => 'New York',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.testmodule.records.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertSuccessful()
        ->assertJsonStructure([
            'records',
            'columns',
        ]);
});

it('can create a new hotel record', function () {
    $data = [
        'hotel_name' => 'Seaside Resort & Spa',
        'contact_number' => '+1-555-0144',
        'email' => 'contact@seasideresort.com',
        'city' => 'Miami',
    ];

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.testmodule.records.store'), $data);

    $response->assertSuccessful()
        ->assertJsonPath('data.hotel_name', 'Seaside Resort & Spa')
        ->assertJsonPath('data.city', 'Miami');

    $this->assertDatabaseHas('test_modules', [
        'hotel_name' => 'Seaside Resort & Spa',
        'city' => 'Miami',
    ]);
});

it('validates required fields when creating a hotel record', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.testmodule.records.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hotel_name', 'contact_number', 'email', 'city']);
});

it('can fetch hotel record details for editing', function () {
    $hotel = TestModule::create([
        'hotel_name' => 'Mountain View Lodge',
        'contact_number' => '+1-555-0188',
        'email' => 'info@mountainview.com',
        'city' => 'Denver',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.testmodule.records.edit', $hotel->id))
        ->assertSuccessful()
        ->assertJsonPath('data.hotel_name', 'Mountain View Lodge')
        ->assertJsonPath('data.city', 'Denver');
});

it('can update an existing hotel record', function () {
    $hotel = TestModule::create([
        'hotel_name' => 'Old Town Hotel',
        'contact_number' => '+1-555-0122',
        'email' => 'info@oldtown.com',
        'city' => 'Boston',
    ]);

    $updatedData = [
        'hotel_name' => 'Historic Old Town Hotel',
        'contact_number' => '+1-555-0123',
        'email' => 'contact@oldtown.com',
        'city' => 'Boston',
    ];

    $this->actingAs($this->admin)
        ->putJson(route('admin.testmodule.records.update', $hotel->id), $updatedData)
        ->assertSuccessful()
        ->assertJsonPath('data.hotel_name', 'Historic Old Town Hotel');

    $this->assertDatabaseHas('test_modules', [
        'id' => $hotel->id,
        'hotel_name' => 'Historic Old Town Hotel',
    ]);
});

it('can delete a hotel record', function () {
    $hotel = TestModule::create([
        'hotel_name' => 'Temporary Hotel',
        'contact_number' => '+1-555-0999',
        'email' => 'temp@hotel.com',
        'city' => 'Chicago',
    ]);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.testmodule.records.destroy', $hotel->id))
        ->assertSuccessful();

    $this->assertDatabaseMissing('test_modules', [
        'id' => $hotel->id,
    ]);
});

it('can mass delete hotel records', function () {
    $hotel1 = TestModule::create([
        'hotel_name' => 'Hotel One',
        'contact_number' => '+1-555-0001',
        'email' => 'one@hotel.com',
        'city' => 'Seattle',
    ]);

    $hotel2 = TestModule::create([
        'hotel_name' => 'Hotel Two',
        'contact_number' => '+1-555-0002',
        'email' => 'two@hotel.com',
        'city' => 'Seattle',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.testmodule.records.mass_delete'), [
            'indices' => [$hotel1->id, $hotel2->id],
        ])
        ->assertSuccessful();

    $this->assertDatabaseMissing('test_modules', ['id' => $hotel1->id]);
    $this->assertDatabaseMissing('test_modules', ['id' => $hotel2->id]);
});
