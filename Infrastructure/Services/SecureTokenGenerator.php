<?php

namespace App\Modules\AuthModule\Infrastructure\Services;

use App\Modules\AuthModule\Ports\Services\TokenGeneratorInterface;

class SecureTokenGenerator implements TokenGeneratorInterface
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}
