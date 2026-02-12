<?php

namespace App\Modules\AuthModule\Infrastructure\Persistence;

use App\Modules\AuthModule\Models\Otp;
use App\Modules\AuthModule\Ports\Repositories\OtpRepositoryInterface;

class EloquentOtpRepository implements OtpRepositoryInterface
{
    public function create(array $data): Otp
    {
        return Otp::create($data);
    }

    public function findLatestValidForUser(int $userId, string $type): ?Otp
    {
        return Otp::where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function markAsVerified(Otp $otp): void
    {
        $otp->update(['verified_at' => now()]);
    }

    public function invalidateAllForUser(int $userId, string $type): void
    {
        Otp::where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);
    }
}
