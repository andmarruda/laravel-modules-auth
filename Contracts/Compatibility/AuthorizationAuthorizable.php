<?php

declare(strict_types=1);

namespace Andmarruda\AuthorizationModule\Contracts;

use Illuminate\Support\Collection;

interface Authorizable
{
    public function getId(): int|string;

    public function getAuthorizableRoles(): Collection;

    public function getAuthorizablePermissions(): Collection;
}
