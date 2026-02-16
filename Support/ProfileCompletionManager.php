<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Models\User;

class ProfileCompletionManager
{
    /**
     * @return array<int, string>
     */
    public function requiredUserFields(): array
    {
        return array_values(array_unique((array) config('authmodule.profile.required_user_fields', ['name'])));
    }

    /**
     * @return array<int, string>
     */
    public function requiredPreferenceKeys(): array
    {
        return array_values(array_unique((array) config('authmodule.profile.required_preference_keys', [])));
    }

    /**
     * @return array{user_fields: array<int, string>, preference_keys: array<int, string>}
     */
    public function missingFields(User $user): array
    {
        $missingUserFields = [];
        foreach ($this->requiredUserFields() as $field) {
            $value = $user->getAttribute($field);

            if (!is_string($value) || trim($value) === '') {
                $missingUserFields[] = $field;
            }
        }

        $missingPreferenceKeys = [];
        foreach ($this->requiredPreferenceKeys() as $key) {
            $value = $user->getPreference($key);

            if (!is_string($value) || trim($value) === '') {
                $missingPreferenceKeys[] = $key;
            }
        }

        return [
            'user_fields' => $missingUserFields,
            'preference_keys' => $missingPreferenceKeys,
        ];
    }

    public function isComplete(User $user): bool
    {
        $missing = $this->missingFields($user);

        return $missing['user_fields'] === [] && $missing['preference_keys'] === [];
    }

    public function markAsCompleteIfPossible(User $user): void
    {
        if (!$this->isComplete($user) || $user->profile_completed_at !== null) {
            return;
        }

        $user->forceFill(['profile_completed_at' => now()])->save();
    }
}
