<?php

use Illuminate\Support\Facades\Route;
use Andmarruda\AuthModule\Http\Controllers\{
    InvitationController,
    UserController,
    SocialAuthController,
    UserPreferenceController
};

Route::group([
    'prefix' => 'invitations',
    'as' => 'invitations.',
], function () {
    Route::post('/create', [InvitationController::class, 'inviteUser'])->name('create');
    Route::post('/accept', [InvitationController::class, 'acceptInvitation'])->name('accept');
});

Route::group([
    'prefix' => 'users',
    'as' => 'users.',
], function () {
    Route::post('/register', [UserController::class, 'register'])->name('register');
});

Route::group([
    'prefix' => 'auth/social',
    'as' => 'auth.social.',
    'middleware' => ['web'],
], function () {
    Route::get('/profile/status', [SocialAuthController::class, 'profileStatus'])
        ->middleware('auth')
        ->name('profile.status');
    Route::post('/profile/complete', [SocialAuthController::class, 'completeProfile'])
        ->middleware('auth')
        ->name('profile.complete');
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
});

Route::group([
    'prefix' => 'auth/preferences',
    'as' => 'auth.preferences.',
], function () {
    Route::post('/', [UserPreferenceController::class, 'upsert'])->name('upsert');
});
