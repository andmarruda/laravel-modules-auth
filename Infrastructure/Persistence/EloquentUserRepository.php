<?php

namespace Andmarruda\AuthModule\Infrastructure\Persistence;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function save(User $user): User
    {
        $user->save();

        return $user;
    }
}
