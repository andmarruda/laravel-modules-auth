<?php

namespace App\Modules\AuthModule\Ports\Services;

interface TokenGeneratorInterface
{
    public function generate(): string;
}
