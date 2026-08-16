<?php

it('shows the help page to an authenticated admin', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.help.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.help.index.title'), false)
        ->assertSee('Cloud Hosting')
        ->assertSee('Extensions')
        ->assertSee('krayincrm.com/cloud-hosting')
        ->assertSee('Still need a hand?')
        ->assertSee('Community Forums')
        ->assertSee('Video Tutorials');
});
