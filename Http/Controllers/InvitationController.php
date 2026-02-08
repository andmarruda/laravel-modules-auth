<?php

namespace App\Modules\AuthModule\Http\Controllers;

use App\Modules\AuthModule\UseCases\AcceptInvitation\AcceptInvitation;
use App\Modules\AuthModule\UseCases\InviteUser\InviteUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InvitationController extends Controller
{
    public function __construct(
        private InviteUser $inviteUser,
        private AcceptInvitation $acceptInvitation,
    ) {}

    public function inviteUser(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'resource_scope' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->inviteUser->execute(
            inviter: $request->user(),
            email: $validated['email'],
            resourceScope: $validated['resource_scope'] ?? null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'email_already_registered' => response()->json([
                    'message' => 'The email has already been registered.',
                    'errors' => ['email' => ['The email has already been registered.']],
                ], 422),
            };
        }

        return response()->json([
            'data' => [
                'id' => $result->invitation->id,
                'email' => $result->invitation->email,
                'resource_scope' => $result->invitation->resource_scope,
                'expires_at' => $result->invitation->expires_at,
            ],
        ], 201);
    }

    public function acceptInvitation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $result = $this->acceptInvitation->execute(
            token: $validated['token'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'not_found' => response()->json(['message' => 'Invitation not found.'], 404),
                'expired' => response()->json(['message' => 'Invitation expired.'], 410),
            };
        }

        return response()->json([
            'data' => [
                'email' => $result->invitation->email,
                'resource_scope' => $result->invitation->resource_scope,
                'accepted_at' => $result->invitation->accepted_at,
            ],
        ]);
    }
}
