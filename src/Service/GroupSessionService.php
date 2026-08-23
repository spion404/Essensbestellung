<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class GroupSessionService
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException(
                'PHP-Sessions sind auf diesem Server deaktiviert.'
            );
        }

        $secureCookie =
            (
                isset($_SERVER['HTTPS'])
                && strtolower((string) $_SERVER['HTTPS']) !== 'off'
                && (string) $_SERVER['HTTPS'] !== ''
            )
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

        session_name('essensbestellung_group');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException(
                'Die Sitzung konnte nicht gestartet werden.'
            );
        }
    }

    public function groupId(): ?int
    {
        $value = $_SESSION['group_id'] ?? null;

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        return null;
    }

    public function login(int $groupId): void
    {
        if ($groupId < 1) {
            throw new RuntimeException(
                'Ungültige Gruppen-ID.'
            );
        }

        if (!session_regenerate_id(true)) {
            throw new RuntimeException(
                'Die Sitzung konnte nicht erneuert werden.'
            );
        }

        $_SESSION['group_id'] = $groupId;
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            $options = [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ];

            if (($params['domain'] ?? '') !== '') {
                $options['domain'] = $params['domain'];
            }

            setcookie(
                session_name(),
                '',
                $options
            );
        }

        session_destroy();
    }

    public function csrfToken(): string
    {
        $token = $_SESSION['csrf_token'] ?? null;

        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(
                random_bytes(32)
            );

            $_SESSION['csrf_token'] = $token;
        }

        return $token;
    }

    public function verifyCsrfToken(string $token): bool
    {
        $storedToken = $_SESSION['csrf_token'] ?? null;

        if (
            !is_string($storedToken)
            || $storedToken === ''
            || $token === ''
        ) {
            return false;
        }

        return hash_equals(
            $storedToken,
            $token
        );
    }
}