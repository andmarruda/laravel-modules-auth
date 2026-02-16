<?php

namespace Andmarruda\AuthModule\Ports\Repositories;

use Andmarruda\AuthModule\Models\Otp;

interface OtpRepositoryInterface
{
    public function create(array $data): Otp;

    public function findLatestValidForUser(int $userId, string $type): ?Otp;

    public function markAsVerified(Otp $otp): void;

    public function invalidateAllForUser(int $userId, string $type): void;
}
