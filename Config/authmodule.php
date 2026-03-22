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
    'jwt' => [
        'algorithm' => env('AUTHMODULE_JWT_ALGORITHM', 'RS256'),
        'secret' => env('AUTHMODULE_JWT_SECRET', env('APP_KEY', '')),
        'private_key' => env('AUTHMODULE_JWT_PRIVATE_KEY', ''),
        'public_key' => env('AUTHMODULE_JWT_PUBLIC_KEY', ''),
        'private_key_passphrase' => env('AUTHMODULE_JWT_PRIVATE_KEY_PASSPHRASE', ''),
        'key_id' => env('AUTHMODULE_JWT_KEY_ID', ''),
        'ttl_minutes' => (int) env('AUTHMODULE_JWT_TTL_MINUTES', 60),
        'issuer' => env('AUTHMODULE_JWT_ISSUER', env('APP_URL', 'authmodule')),
        'leeway_seconds' => (int) env('AUTHMODULE_JWT_LEEWAY_SECONDS', 0),
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
