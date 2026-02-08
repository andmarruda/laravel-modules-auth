<?php

namespace App\Modules\AuthModule\Ports\Repositories;

use App\Modules\AuthModule\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;

    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;
}
