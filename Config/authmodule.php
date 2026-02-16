<?php

return [
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
