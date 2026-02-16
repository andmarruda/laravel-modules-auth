<?php

namespace Andmarruda\AuthModule\Http\Middleware;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Support\ProfileCompletionManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSocialProfileIsComplete
{
    public function __construct(private ProfileCompletionManager $profileCompletion)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof User || $this->profileCompletion->isComplete($user)) {
            return $next($request);
        }

        $missing = $this->profileCompletion->missingFields($user);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'Profile completion required.',
                'missing_fields' => $missing,
            ], 409);
        }

        $onboardingPath = (string) config('authmodule.profile.redirect_to_onboarding', '/complete-profile');

        return redirect($onboardingPath);
    }
}
