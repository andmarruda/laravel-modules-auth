<?php

namespace Andmarruda\AuthModule\UseCases\Register;

use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;

class RegisterOpenUser
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function execute(string $name, string $email, string $password): RegisterOpenUserResult
    {
        $normalizedEmail = strtolower(trim($email));

        if ($this->users->emailExists($normalizedEmail)) {
            return RegisterOpenUserResult::emailAlreadyRegistered();
        }

        $user = $this->users->create([
            'name' => $name,
            'email' => $normalizedEmail,
            'password' => $password,
        ]);

        return RegisterOpenUserResult::success($user);
    }
}
