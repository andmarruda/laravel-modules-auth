<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Contracts\AuthenticatedUserPayloadExtenderInterface;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuthenticatedUserPayloadBuilder
{
    public function __construct(
        private UserSettingsManager $settings,
        private AuthenticatedUserPayloadExtenderInterface $extender,
    ) {
    }

    /**
     * @param array<string, mixed> $auth
     * @return array<string, mixed>
     */
    public function buildAuthResponse(User $user, Request $request, array $auth): array
    {
        return array_merge($auth, $this->buildBootstrapPayload($user, $request));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildBootstrapPayload(User $user, Request $request): array
    {
        $extras = $this->extender->extend($user, $request);
        $roles = $this->extractStringList($user, ['roles', 'getRoles', 'getRoleNames']);
        $permissions = $this->extractStringList($user, ['permissions', 'getPermissions', 'getPermissionNames']);

        $userPayload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_image' => $user->profile_image,
            'theme_mode' => $user->theme_mode,
            'settings' => $this->settings->all($user),
        ];

        return array_merge([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'user' => $userPayload,
            'roles' => $roles,
            'permissions' => $permissions,
            'plan' => $extras['plan'] ?? null,
        ], $extras);
    }

    /**
     * @param array<int, string> $methods
     * @return array<int, string>
     */
    private function extractStringList(User $user, array $methods): array
    {
        foreach ($methods as $method) {
            if (!method_exists($user, $method)) {
                continue;
            }

            $value = $user->{$method}();

            if ($value instanceof Relation) {
                return $this->normalizeStringList($value->get()->all());
            }

            if ($value instanceof Collection) {
                return $this->normalizeStringList($value->all());
            }

            if (is_iterable($value)) {
                return $this->normalizeStringList($value);
            }

            if (is_array($value)) {
                return $this->normalizeStringList($value);
            }
        }

        return [];
    }

    /**
     * @param iterable<mixed> $items
     * @return array<int, string>
     */
    private function normalizeStringList(iterable $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $normalized[] = $item;
                continue;
            }

            if (is_object($item)) {
                foreach (['name', 'slug', 'key', 'code'] as $attribute) {
                    $value = data_get($item, $attribute);
                    if (is_string($value) && $value !== '') {
                        $normalized[] = $value;
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($normalized));
    }
}
