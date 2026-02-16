<?php

namespace Andmarruda\AuthModule\Infrastructure\Services;

use Andmarruda\AuthModule\Ports\Services\TokenGeneratorInterface;

class SecureTokenGenerator implements TokenGeneratorInterface
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}
