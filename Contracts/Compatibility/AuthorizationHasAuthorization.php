<?php

declare(strict_types=1);

namespace Andmarruda\AuthorizationModule\Traits;

use Illuminate\Support\Collection;

trait HasAuthorization
{
    public function getId(): int|string
    {
        return $this->getKey();
    }

    public function getAuthorizableRoles(): Collection
    {
        if (method_exists($this, 'roles')) {
            return $this->roles instanceof Collection ? $this->roles : collect($this->roles);
        }

        return collect();
    }

    public function getAuthorizablePermissions(): Collection
    {
        if (method_exists($this, 'getAllPermissions')) {
            $permissions = $this->getAllPermissions();

            return $permissions instanceof Collection ? $permissions : collect($permissions);
        }

        return collect();
    }
}
