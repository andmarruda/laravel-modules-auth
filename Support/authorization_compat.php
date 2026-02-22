<?php

declare(strict_types=1);

if (!interface_exists(\Andmarruda\AuthorizationModule\Contracts\Authorizable::class)) {
    require_once __DIR__ . '/../Contracts/Compatibility/AuthorizationAuthorizable.php';
}

if (!trait_exists(\Andmarruda\AuthorizationModule\Traits\HasAuthorization::class)) {
    require_once __DIR__ . '/../Contracts/Compatibility/AuthorizationHasAuthorization.php';
}
