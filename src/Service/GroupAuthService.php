<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GroupRepository;
use Throwable;

final class GroupAuthService
{
    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly EncryptionService $encryptionService
    ) {
    }

    public function authenticate(
        int $groupId,
        string $password
    ): ?array {
        if ($groupId < 1 || $password === '') {
            return null;
        }

        $group = $this->groupRepository->findById(
            $groupId
        );

        if ($group === null) {
            return null;
        }

        try {
            $storedPassword =
                $this->encryptionService->decrypt(
                    (string) $group['password_encrypted']
                );
        } catch (Throwable) {
            return null;
        }

        if (
            !hash_equals(
                $storedPassword,
                $password
            )
        ) {
            return null;
        }

        return $group;
    }
}