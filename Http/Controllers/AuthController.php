<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Support\AuthenticatedGuardResolver;
use Andmarruda\AuthModule\Support\AuthenticatedUserPayloadBuilder;
use Andmarruda\AuthModule\Support\AuthenticatedUserResolver;
use Andmarruda\AuthModule\Support\AuthChannelManager;
use Andmarruda\AuthModule\UseCases\PasswordReset\RequestPasswordReset;
use Andmarruda\AuthModule\UseCases\PasswordReset\ResetPasswordWithOtp;
use Andmarruda\AuthModule\UseCases\Register\RegisterOpenUser;
use Andmarruda\AuthModule\UseCases\Register\RegisterUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private AuthChannelManager $channels,
        private AuthenticatedUserPayloadBuilder $payloads,
        private RegisterOpenUser $registerOpenUser,
        private RegisterUser $registerUser,
        private RequestPasswordReset $requestPasswordReset,
        private ResetPasswordWithOtp $resetPasswordWithOtp,
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'channel' => ['nullable', 'string', 'in:session,sanctum,jwt'],
            'remember' => ['nullable', 'boolean'],
            'token_name' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', strtolower(trim($validated['email'])))->first();

        if (!$user instanceof User || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return $this->authResponse($user, $request, $validated['channel'] ?? null);
    }

    public function me(Request $request): JsonResponse
    {
        $user = AuthenticatedUserResolver::resolve($request);

        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json($this->payloads->buildBootstrapPayload($user, $request));
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = AuthenticatedUserResolver::resolve($request);

        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $channel = $request->input('channel');

        if (!is_string($channel) || $channel === '') {
            $channel = AuthenticatedGuardResolver::guardToChannel(
                AuthenticatedGuardResolver::resolve($request),
            );
        }

        return $this->authResponse($user, $request, $channel, true);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = AuthenticatedUserResolver::resolve($request);

        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $channel = $request->input('channel');

        if (!is_string($channel) || $channel === '') {
            $channel = AuthenticatedGuardResolver::guardToChannel(
                AuthenticatedGuardResolver::resolve($request),
            );
        }

        try {
            $this->channels->resolve($channel)->logout($user, $request);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'channel' => ['nullable', 'string', 'in:session,sanctum,jwt'],
            'invitation_token' => ['nullable', 'string'],
            'token' => ['nullable', 'string'],
            'remember' => ['nullable', 'boolean'],
            'token_name' => ['nullable', 'string', 'max:255'],
        ]);

        $invitationToken = $validated['invitation_token'] ?? $validated['token'] ?? null;

        if (is_string($invitationToken) && $invitationToken !== '') {
            $result = $this->registerUser->execute(
                token: $invitationToken,
                name: $validated['name'],
                password: $validated['password'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            if (!$result->success) {
                return match ($result->error) {
                    'invitation_not_found' => response()->json(['message' => 'Invitation not found.'], 404),
                    'invitation_already_used' => response()->json(['message' => 'Invitation already used.'], 410),
                    'invitation_expired' => response()->json(['message' => 'Invitation expired.'], 410),
                };
            }

            return $this->authResponse($result->user, $request, $validated['channel'] ?? null, false, 201);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $result = $this->registerOpenUser->execute(
            name: $validated['name'],
            email: (string) $validated['email'],
            password: $validated['password'],
        );

        if (!$result->success) {
            return response()->json([
                'message' => 'The email has already been registered.',
                'errors' => ['email' => ['The email has already been registered.']],
            ], 422);
        }

        return $this->authResponse($result->user, $request, $validated['channel'] ?? null, false, 201);
    }

    public function passwordReset(Request $request): JsonResponse
    {
        $hasResetPayload = $request->filled('code') || $request->filled('password');

        if (!$hasResetPayload) {
            $validated = $request->validate([
                'email' => ['required', 'email'],
            ]);

            $result = $this->requestPasswordReset->execute(
                email: strtolower(trim($validated['email'])),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            if ($result->error === 'throttled') {
                return response()->json(['message' => 'Password reset is temporarily throttled.'], 429);
            }

            return response()->json(['success' => true]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->resetPasswordWithOtp->execute(
            email: strtolower(trim($validated['email'])),
            code: $validated['code'],
            newPassword: $validated['password'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'user_not_found' => response()->json(['message' => 'User not found.'], 404),
                'otp_not_found' => response()->json(['message' => 'Password reset code not found or expired.'], 404),
                'invalid_code' => response()->json(['message' => 'Invalid password reset code.'], 422),
                default => response()->json(['message' => 'Unable to reset password.'], 422),
            };
        }

        return response()->json(['success' => true]);
    }

    private function authResponse(
        User $user,
        Request $request,
        ?string $channel,
        bool $refresh = false,
        int $status = 200,
    ): JsonResponse {
        try {
            $authChannel = $this->channels->resolve($channel);
            $auth = $refresh
                ? $authChannel->refresh($user, $request)
                : $authChannel->authenticate($user, $request);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(
            $this->payloads->buildAuthResponse($user, $request, $auth),
            $status,
        );
    }
}
