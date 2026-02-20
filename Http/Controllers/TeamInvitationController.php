<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Andmarruda\AuthModule\Contracts\TeamInvitationInviterAuthorizerInterface;
use Andmarruda\AuthModule\Infrastructure\Mail\TeamInvitationMail;
use Andmarruda\AuthModule\Models\Team;
use Andmarruda\AuthModule\Models\TeamInvitation;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Services\TokenGeneratorInterface;
use Andmarruda\AuthModule\Support\AuthenticatedUserResolver;
use Andmarruda\AuthModule\Support\DefaultTeamInvitationInviterAuthorizer;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

class TeamInvitationController extends Controller
{
    public function __construct(private TokenGeneratorInterface $tokenGenerator)
    {
    }

    public function invite(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'email' => ['required', 'email'],
            'role' => ['nullable', 'string', 'in:member,admin'],
            'inviter_type' => ['nullable', 'string', 'max:255'],
            'inviter_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $team = Team::findOrFail($validated['team_id']);
        if (!$this->canInviteToTeam($user, $team)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $inviter = $this->resolveInviter($validated, $user);
        if (!$inviter instanceof EloquentModel) {
            return response()->json(['message' => 'Invalid inviter context.'], 422);
        }

        if (!$this->inviterAuthorizer()->authorize($user, $inviter, $team)) {
            return response()->json(['message' => 'Forbidden inviter context.'], 403);
        }

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => strtolower($validated['email']),
            'token' => $this->tokenGenerator->generate(),
            'inviter_id' => $inviter->getKey(),
            'inviter_type' => $inviter::class,
            'role' => $validated['role'] ?? 'member',
            'expires_at' => now()->addDays((int) config('authmodule.teams.invitation_ttl_days', 7)),
        ]);

        $acceptUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/')
            . '/teams/invitations/accept?token='
            . $invitation->token;

        Mail::to($invitation->email)->queue(new TeamInvitationMail($invitation, $acceptUrl));

        return response()->json([
            'data' => [
                'id' => $invitation->id,
                'team_id' => $invitation->team_id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'inviter_type' => $invitation->inviter_type,
                'inviter_id' => $invitation->inviter_id,
                'expires_at' => $invitation->expires_at,
            ],
        ], 201);
    }

    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $invitation = TeamInvitation::with('team')->where('token', $validated['token'])->first();
        if (!$invitation instanceof TeamInvitation) {
            return response()->json(['message' => 'Invitation not found.'], 404);
        }

        if ($invitation->isExpired()) {
            return response()->json(['message' => 'Invitation expired.'], 410);
        }

        if ($invitation->isAccepted()) {
            return response()->json(['message' => 'Invitation already used.'], 410);
        }

        $hasAccount = User::where('email', $invitation->email)->exists();

        return response()->json([
            'data' => [
                'team' => [
                    'id' => $invitation->team->id,
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
                'email' => $invitation->email,
                'role' => $invitation->role,
                'has_account' => $hasAccount,
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }

    public function redeem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $invitation = TeamInvitation::with('team')->where('token', $validated['token'])->first();
        if (!$invitation instanceof TeamInvitation) {
            return response()->json(['message' => 'Invitation not found.'], 404);
        }

        if ($invitation->isExpired()) {
            return response()->json(['message' => 'Invitation expired.'], 410);
        }

        $user = $this->resolveAuthenticatedUser($request);
        if (!$user instanceof User) {
            return response()->json([
                'data' => [
                    'status' => 'requires_authentication',
                    'next_action' => 'register_or_login',
                    'email' => $invitation->email,
                    'team_id' => $invitation->team_id,
                    'team_name' => $invitation->team->name,
                ],
            ]);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return response()->json(['message' => 'Invitation email does not match the authenticated user.'], 403);
        }

        if ($invitation->isAccepted()) {
            $isMember = $invitation->team->users()
                ->where('users.id', $user->id)
                ->exists();

            return response()->json([
                'data' => [
                    'status' => $isMember ? 'already_member' : 'invitation_already_used',
                    'team_id' => $invitation->team_id,
                ],
            ]);
        }

        $membership = $this->attachUserToTeam($user, $invitation);
        if (!$membership['ok']) {
            return response()->json(['message' => $membership['message']], 422);
        }

        $invitation->update(['accepted_at' => now()]);

        return response()->json([
            'data' => [
                'status' => $membership['status'],
                'team_id' => $invitation->team_id,
                'role' => $invitation->role,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $invitation = TeamInvitation::with('team')->where('token', $validated['token'])->first();
        if (!$invitation instanceof TeamInvitation) {
            return response()->json(['message' => 'Invitation not found.'], 404);
        }

        if ($invitation->isExpired()) {
            return response()->json(['message' => 'Invitation expired.'], 410);
        }

        if ($invitation->isAccepted()) {
            return response()->json(['message' => 'Invitation already used.'], 410);
        }

        $existingUser = User::where('email', $invitation->email)->first();
        if ($existingUser instanceof User) {
            return response()->json([
                'message' => 'An account already exists for this invitation email. Please login and redeem the invitation.',
            ], 409);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => $validated['password'],
            'article_coins_balance' => 2,
        ]);

        $membership = $this->attachUserToTeam($user, $invitation);
        if (!$membership['ok']) {
            return response()->json(['message' => $membership['message']], 422);
        }

        $invitation->update(['accepted_at' => now()]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'team_id' => $invitation->team_id,
                'role' => $invitation->role,
            ],
        ], 201);
    }

    private function canInviteToTeam(User $user, Team $team): bool
    {
        if ($user->isManager()) {
            return true;
        }

        if ($team->owner_id === $user->id) {
            return true;
        }

        return $team->users()
            ->where('users.id', $user->id)
            ->whereIn('team_user.role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * @return array{ok: bool, status: string, message: string}
     */
    private function attachUserToTeam(User $user, TeamInvitation $invitation): array
    {
        $alreadyMember = $invitation->team->users()
            ->where('users.id', $user->id)
            ->exists();

        if ($alreadyMember) {
            return [
                'ok' => true,
                'status' => 'already_member',
                'message' => '',
            ];
        }

        $maxTeams = config('authmodule.teams.max_teams_per_user');
        if (is_numeric($maxTeams) && (int) $maxTeams > 0) {
            $teamsCount = $user->teams()->count();
            if ($teamsCount >= (int) $maxTeams) {
                return [
                    'ok' => false,
                    'status' => 'team_limit_reached',
                    'message' => 'The user reached the maximum number of teams allowed for this app.',
                ];
            }
        }

        $invitation->team->users()->attach($user->id, [
            'role' => $invitation->role,
            'joined_at' => now(),
        ]);

        return [
            'ok' => true,
            'status' => 'joined',
            'message' => '',
        ];
    }

    private function resolveAuthenticatedUser(Request $request): ?User
    {
        return AuthenticatedUserResolver::resolve($request);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveInviter(array $validated, User $defaultInviter): ?EloquentModel
    {
        $rawType = $validated['inviter_type'] ?? null;
        $rawId = $validated['inviter_id'] ?? null;

        if ($rawType === null && $rawId === null) {
            return $defaultInviter;
        }

        if (!is_string($rawType) || trim($rawType) === '') {
            return null;
        }

        if (!is_int($rawId) && !(is_string($rawId) && is_numeric($rawId))) {
            return null;
        }

        $resolvedId = (int) $rawId;
        if ($resolvedId < 1) {
            return null;
        }

        $modelClass = $this->allowedInviterModels()[$rawType] ?? $rawType;
        if (!class_exists($modelClass)) {
            return null;
        }

        if (!is_subclass_of($modelClass, EloquentModel::class)) {
            return null;
        }

        if (!in_array($modelClass, $this->allowedInviterModels(), true)) {
            return null;
        }

        /** @var class-string<EloquentModel> $modelClass */
        return $modelClass::query()->find($resolvedId);
    }

    /**
     * @return array<string, class-string<EloquentModel>>
     */
    private function allowedInviterModels(): array
    {
        $configured = (array) config('authmodule.teams.inviter_models', [
            'user' => User::class,
        ]);

        $allowed = [];
        foreach ($configured as $alias => $modelClass) {
            if (!is_string($alias) || !is_string($modelClass)) {
                continue;
            }

            if (!class_exists($modelClass)) {
                continue;
            }

            if (!is_subclass_of($modelClass, EloquentModel::class)) {
                continue;
            }

            $allowed[$alias] = $modelClass;
        }

        if ($allowed === []) {
            $allowed['user'] = User::class;
        }

        return $allowed;
    }

    private function inviterAuthorizer(): TeamInvitationInviterAuthorizerInterface
    {
        $configured = config('authmodule.teams.inviter_authorizer', DefaultTeamInvitationInviterAuthorizer::class);
        $authorizerClass = is_string($configured) && $configured !== ''
            ? $configured
            : DefaultTeamInvitationInviterAuthorizer::class;

        if (!class_exists($authorizerClass)) {
            return app(DefaultTeamInvitationInviterAuthorizer::class);
        }

        $authorizer = app($authorizerClass);
        if ($authorizer instanceof TeamInvitationInviterAuthorizerInterface) {
            return $authorizer;
        }

        return app(DefaultTeamInvitationInviterAuthorizer::class);
    }
}
