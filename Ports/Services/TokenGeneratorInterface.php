<?php

namespace Andmarruda\AuthModule\Ports\Services;

interface TokenGeneratorInterface
{
    public function generate(): string;
}
