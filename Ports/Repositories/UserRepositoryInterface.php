<?php

namespace Andmarruda\AuthModule\Ports\Repositories;

use Andmarruda\AuthModule\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;

    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;

    public function findById(int $id): ?User;

    public function save(User $user): User;
}
