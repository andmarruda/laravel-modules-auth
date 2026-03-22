<?php

use Illuminate\Support\Facades\Route;
use Andmarruda\AuthModule\Http\Controllers\{
    InvitationController,
    JwtAuthController,
    UserController,
    SocialAuthController,
    UserPreferenceController,
    TeamController,
    TeamInvitationController
};
use Andmarruda\AuthModule\Support\AuthMiddleware;

$invitationCreateMiddleware = AuthMiddleware::fromGuards(
    (array) config('authmodule.auth.invitation_create_guards', ['web', 'sanctum']),
);
$socialProfileMiddleware = AuthMiddleware::fromGuards(
    (array) config('authmodule.auth.social_profile_guards', ['web', 'sanctum']),
);
$preferencesMiddleware = AuthMiddleware::fromGuards(
    (array) config('authmodule.auth.preferences_guards', ['web', 'sanctum']),
);
$teamsMiddleware = AuthMiddleware::fromGuards(
    (array) config('authmodule.auth.teams_guards', ['web', 'sanctum']),
);

Route::group([
    'prefix' => 'invitations',
    'as' => 'invitations.',
], function () use ($invitationCreateMiddleware) {
    Route::post('/create', [InvitationController::class, 'inviteUser'])
        ->middleware($invitationCreateMiddleware)
        ->name('create');
    Route::post('/accept', [InvitationController::class, 'acceptInvitation'])->name('accept');
});

Route::group([
    'prefix' => 'users',
    'as' => 'users.',
], function () {
    Route::post('/register', [UserController::class, 'register'])->name('register');
});

Route::group([
    'prefix' => 'auth/jwt',
    'as' => 'auth.jwt.',
], function () {
    Route::post('/token', [JwtAuthController::class, 'token'])->name('token');
});

Route::group([
    'prefix' => 'auth/social',
    'as' => 'auth.social.',
    'middleware' => ['web'],
], function () use ($socialProfileMiddleware) {
    Route::get('/profile/status', [SocialAuthController::class, 'profileStatus'])
        ->middleware($socialProfileMiddleware)
        ->name('profile.status');
    Route::post('/profile/complete', [SocialAuthController::class, 'completeProfile'])
        ->middleware($socialProfileMiddleware)
        ->name('profile.complete');
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
});

Route::group([
    'prefix' => 'auth/preferences',
    'as' => 'auth.preferences.',
], function () use ($preferencesMiddleware) {
    Route::post('/', [UserPreferenceController::class, 'upsert'])
        ->middleware($preferencesMiddleware)
        ->name('upsert');
});

Route::group([
    'prefix' => 'teams',
    'as' => 'teams.',
], function () use ($teamsMiddleware) {
    Route::post('/', [TeamController::class, 'create'])
        ->middleware($teamsMiddleware)
        ->name('create');
    Route::get('/mine', [TeamController::class, 'mine'])
        ->middleware($teamsMiddleware)
        ->name('mine');

    Route::group([
        'prefix' => 'invitations',
        'as' => 'invitations.',
    ], function () use ($teamsMiddleware) {
        Route::post('/create', [TeamInvitationController::class, 'invite'])
            ->middleware($teamsMiddleware)
            ->name('create');
        Route::get('/resolve', [TeamInvitationController::class, 'resolve'])->name('resolve');
        Route::post('/redeem', [TeamInvitationController::class, 'redeem'])->name('redeem');
        Route::post('/register', [TeamInvitationController::class, 'register'])->name('register');
    });
});
