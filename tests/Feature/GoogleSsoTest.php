<?php

use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Webkul\User\Models\User;

test('login page contains google sign in button', function () {
    $response = $this->get(route('admin.session.create'));

    $response->assertStatus(200)
        ->assertSee('Sign in with Google');
});

test('google sso redirect route redirects to google auth', function () {
    $response = $this->get(route('admin.auth.socialite.redirect', 'google'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('google sso callback successfully authenticates existing active user', function () {
    $user = User::first();
    if (! $user) {
        $user = User::create([
            'name' => 'Active Admin',
            'email' => 'admin_sso@example.com',
            'password' => bcrypt('password123'),
            'status' => 1,
            'role_id' => 1,
        ]);
    } else {
        $user->update(['status' => 1]);
    }

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-123456');
    $socialiteUser->shouldReceive('getEmail')->andReturn($user->email);
    $socialiteUser->shouldReceive('getName')->andReturn($user->name);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('admin.auth.socialite.callback', 'google'));

    $response->assertRedirect(route('admin.dashboard.index'));
    $this->assertAuthenticatedAs($user, 'user');

    $user->refresh();
    expect($user->oauth_provider)->toBe('google');
    expect($user->oauth_id)->toBe('google-123456');
});

test('google sso callback rejects inactive user', function () {
    $user = User::create([
        'name' => 'Inactive User',
        'email' => 'inactive_user_'.uniqid().'@example.com',
        'password' => bcrypt('password123'),
        'status' => 0,
        'role_id' => 1,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-inactive-123');
    $socialiteUser->shouldReceive('getEmail')->andReturn($user->email);
    $socialiteUser->shouldReceive('getName')->andReturn('Inactive User');

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('admin.auth.socialite.callback', 'google'));

    $response->assertRedirect(route('admin.session.create'));
    $this->assertGuest('user');
});

test('google sso callback rejects unregistered user email', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-unknown-999');
    $socialiteUser->shouldReceive('getEmail')->andReturn('unregistered_'.uniqid().'@company.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Unregistered Person');

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('admin.auth.socialite.callback', 'google'));

    $response->assertRedirect(route('admin.session.create'));
    $this->assertGuest('user');
});
