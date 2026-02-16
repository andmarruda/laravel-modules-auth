<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Andmarruda\AuthModule\Models\SocialAccount;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Support\ProfileCompletionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Illuminate\Routing\Controller;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct(private ProfileCompletionManager $profileCompletion)
    {
    }

    public function redirect(string $provider): RedirectResponse
    {
        if (! $this->isAllowedProvider($provider)) {
            abort(404);
        }

        return Socialite::driver($provider)
            ->scopes($this->scopesFor($provider))
            ->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        if (! $this->isAllowedProvider($provider)) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
            $user = $this->resolveUserFromSocialAccount($provider, $socialUser);

            Auth::login($user, true);

            if (!$this->profileCompletion->isComplete($user)) {
                return $this->redirectToConfiguredPath('redirect_to_onboarding', 'profile');
            }

            $this->profileCompletion->markAsCompleteIfPossible($user);

            return $this->redirectToConfiguredPath('redirect_after_login');
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToConfiguredPath('redirect_after_error')
                ->with('error', 'Social login failed.');
        }
    }

    public function profileStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $missing = $this->profileCompletion->missingFields($user);

        return response()->json([
            'data' => [
                'is_complete' => $this->profileCompletion->isComplete($user),
                'missing_fields' => $missing,
            ],
        ]);
    }

    public function completeProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $requiredPreferenceKeys = $this->profileCompletion->requiredPreferenceKeys();
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('name', $validated) && is_string($validated['name'])) {
            $user->name = $validated['name'];
            $user->save();
        }

        $preferences = (array) ($validated['preferences'] ?? []);
        foreach ($requiredPreferenceKeys as $key) {
            if (!array_key_exists($key, $preferences)) {
                continue;
            }

            $value = $preferences[$key];
            $user->setPreference($key, is_string($value) ? $value : null);
        }

        $user = $user->fresh() ?? $user;
        $missing = $this->profileCompletion->missingFields($user);
        if ($missing['user_fields'] !== [] || $missing['preference_keys'] !== []) {
            return response()->json([
                'message' => 'Profile data is still incomplete.',
                'missing_fields' => $missing,
            ], 422);
        }

        $user = $user->fresh() ?? $user;
        $this->profileCompletion->markAsCompleteIfPossible($user);

        return response()->json([
            'data' => [
                'is_complete' => true,
                'missing_fields' => [
                    'user_fields' => [],
                    'preference_keys' => [],
                ],
            ],
        ]);
    }

    private function isAllowedProvider(string $provider): bool
    {
        $allowedProviders = config('authmodule.social.providers', ['google', 'github']);

        return in_array($provider, $allowedProviders, true);
    }

    /**
     * @return array<int, string>
     */
    private function scopesFor(string $provider): array
    {
        return (array) config("authmodule.social.scopes.{$provider}", []);
    }

    private function resolveUserFromSocialAccount(string $provider, SocialiteUser $socialUser): User
    {
        $providerId = (string) $socialUser->getId();

        if ($providerId === '') {
            throw new \RuntimeException('Invalid provider user id.');
        }

        $existingAccount = SocialAccount::with('user')
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($existingAccount !== null) {
            return $existingAccount->user;
        }

        $email = strtolower(trim((string) ($socialUser->getEmail() ?? '')));
        if ($email === '') {
            throw new \RuntimeException('Provider did not return an email address.');
        }

        $name = trim((string) ($socialUser->getName() ?: $socialUser->getNickname() ?: 'User'));

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Str::random(64),
                'email_verified_at' => now(),
                'is_manager' => false,
                'article_coins_balance' => 2,
            ],
        );

        SocialAccount::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $providerId],
            [
                'user_id' => $user->id,
                'provider_email' => $email,
                'provider_name' => $name,
            ],
        );

        return $user;
    }

    private function redirectToConfiguredPath(string $configKey, string $section = 'social'): RedirectResponse
    {
        $target = (string) config("authmodule.{$section}.{$configKey}", '/');

        if (Str::startsWith($target, ['http://', 'https://'])) {
            return redirect()->away($target);
        }

        return redirect($target);
    }
}
