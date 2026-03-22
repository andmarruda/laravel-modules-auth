<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Models\User;

class UserSettingsManager
{
    /**
     * @return array<string, mixed>
     */
    public function all(User $user): array
    {
        $values = [];

        foreach ($this->definitions() as $key => $definition) {
            $values[$key] = $this->getValue($user, $key, $definition);
        }

        return $values;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->definitions() as $key => $definition) {
            $rules[$key] = array_merge(['sometimes'], (array) ($definition['rules'] ?? ['nullable']));
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function update(User $user, array $settings): array
    {
        foreach ($settings as $key => $value) {
            $definition = $this->definitions()[$key] ?? null;
            if (!is_array($definition)) {
                continue;
            }

            $storage = (string) ($definition['storage'] ?? 'preference');

            if ($storage === 'attribute') {
                $attribute = (string) ($definition['attribute'] ?? $key);
                $user->{$attribute} = $value;
                continue;
            }

            $user->setPreference((string) ($definition['key'] ?? $key), $value !== null ? (string) $value : null);
        }

        $user->save();

        return $this->all($user);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return (array) config('authmodule.settings.definitions', [
            'theme_mode' => [
                'storage' => 'attribute',
                'attribute' => 'theme_mode',
                'rules' => ['nullable', 'string', 'max:50'],
                'default' => null,
            ],
            'theme_mobile_mode' => [
                'storage' => 'preference',
                'key' => 'theme_mobile_mode',
                'rules' => ['nullable', 'string', 'max:50'],
                'default' => null,
            ],
            'monthly_salary' => [
                'storage' => 'preference',
                'key' => 'monthly_salary',
                'rules' => ['nullable', 'numeric'],
                'default' => null,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function getValue(User $user, string $key, array $definition): mixed
    {
        $storage = (string) ($definition['storage'] ?? 'preference');

        if ($storage === 'attribute') {
            $attribute = (string) ($definition['attribute'] ?? $key);

            return $user->{$attribute} ?? ($definition['default'] ?? null);
        }

        return $user->getPreference(
            (string) ($definition['key'] ?? $key),
            isset($definition['default']) ? (string) $definition['default'] : null,
        );
    }
}
