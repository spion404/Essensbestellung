<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class AdminAuthService
{
    public function __construct(
        private readonly string $passwordHash
    ) {
    }

    public function authenticate(string $password): bool
    {
        if ($this->passwordHash === '') {
            throw new RuntimeException(
                'ADMIN_PASSWORD_HASH ist noch nicht konfiguriert.'
            );
        }

        if ($password === '') {
            return false;
        }

        return password_verify($password, $this->passwordHash);
    }
}