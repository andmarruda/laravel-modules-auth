<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Modules\AuthModule\Http\Controllers\{
    InvitationController,
    UserController,
    AuthController
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