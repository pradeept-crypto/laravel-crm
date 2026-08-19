<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Models\User;

class SocialiteController extends Controller
{
    /**
     * Supported SSO providers.
     */
    protected array $supportedProviders = ['google'];

    /**
     * Redirect the user to the OAuth provider's authentication page.
     */
    public function redirectToProvider(string $provider): RedirectResponse
    {
        if (! in_array($provider, $this->supportedProviders, true)) {
            abort(404, 'SSO provider not supported');
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from OAuth provider and authenticate into CRM.
     */
    public function handleProviderCallback(string $provider): RedirectResponse
    {
        if (! in_array($provider, $this->supportedProviders, true)) {
            abort(404, 'SSO provider not supported');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::error("SSO authentication callback error ({$provider})", [
                'message' => $e->getMessage(),
            ]);

            session()->flash('error', "Authentication failed: {$e->getMessage()}");

            return redirect()->route('admin.session.create');
        }

        $email = $socialUser->getEmail();
        $oauthId = $socialUser->getId();

        if (! $email) {
            session()->flash('error', 'Unable to retrieve email from identity provider.');

            return redirect()->route('admin.session.create');
        }

        // 1. Locate user in CRM by oauth_id or email
        $user = User::where('oauth_id', $oauthId)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            session()->flash('error', "No AUURA CRM account found for email: {$email}. Please contact your administrator.");

            return redirect()->route('admin.session.create');
        }

        // 2. Check if account is active
        if ($user->status == 0) {
            session()->flash('warning', trans('admin::app.users.activate-warning'));

            return redirect()->route('admin.session.create');
        }

        // 3. Link or update OAuth provider details
        $user->update([
            'oauth_provider' => $provider,
            'oauth_id' => $oauthId,
        ]);

        // 4. Authenticate session with CRM 'user' guard
        auth()->guard('user')->login($user, true);

        $intendedUrl = session()->pull('url.intended', route('admin.dashboard.index'));

        return redirect()->to($intendedUrl);
    }
}
