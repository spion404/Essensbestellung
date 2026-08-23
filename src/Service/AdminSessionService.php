<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class AdminSessionService
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

        session_name('essensbestellung_admin');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException(
                'Die Admin-Sitzung konnte nicht gestartet werden.'
            );
        }
    }

    public function isAuthenticated(): bool
    {
        return ($_SESSION['admin_authenticated'] ?? false) === true;
    }

    public function login(): void
    {
        if (!session_regenerate_id(true)) {
            throw new RuntimeException(
                'Die Admin-Sitzung konnte nicht erneuert werden.'
            );
        }

        $_SESSION['admin_authenticated'] = true;

        $_SESSION['admin_csrf_token'] = bin2hex(
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
        $token = $_SESSION['admin_csrf_token'] ?? null;

        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(
                random_bytes(32)
            );

            $_SESSION['admin_csrf_token'] = $token;
        }

        return $token;
    }

    public function verifyCsrfToken(string $token): bool
    {
        $storedToken = $_SESSION['admin_csrf_token'] ?? null;

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