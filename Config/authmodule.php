<?php

return [
    'auth' => [
        'default_guard' => env('AUTHMODULE_DEFAULT_GUARD', 'web'),
        'session_guard' => env('AUTHMODULE_SESSION_GUARD', 'web'),
        'api_guard' => env('AUTHMODULE_API_GUARD', 'sanctum'),
        'invitation_create_guards' => ['web', 'sanctum'],
        'social_profile_guards' => ['web', 'sanctum'],
        'preferences_guards' => ['web', 'sanctum'],
        'teams_guards' => ['web', 'sanctum'],
    ],
    'teams' => [
        'max_teams_per_user' => null,
        'invitation_ttl_days' => 7,
        'inviter_models' => [
            'user' => \Andmarruda\AuthModule\Models\User::class,
            // 'tenant' => \App\Models\Tenant::class,
        ],
        'inviter_authorizer' => \Andmarruda\AuthModule\Support\DefaultTeamInvitationInviterAuthorizer::class,
    ],
    'social' => [
        'providers' => ['google', 'github'],
        'scopes' => [
            'google' => ['openid', 'profile', 'email'],
            'github' => ['user:email'],
        ],
        'redirect_after_login' => env('AUTHMODULE_SOCIAL_REDIRECT_AFTER_LOGIN', '/'),
        'redirect_after_error' => env('AUTHMODULE_SOCIAL_REDIRECT_AFTER_ERROR', '/login'),
    ],
    'profile' => [
        'required_user_fields' => ['name'],
        'required_preference_keys' => [],
        'redirect_to_onboarding' => env('AUTHMODULE_PROFILE_ONBOARDING_PATH', '/complete-profile'),
    ],
];
