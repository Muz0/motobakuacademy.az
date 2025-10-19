<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (!$token || !$sessionToken) {
            return false;
        }

        $isValid = hash_equals($sessionToken, $token);

        if ($isValid) {
            // Rotate token after successful validation to reduce replay risk.
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $isValid;
    }
}
