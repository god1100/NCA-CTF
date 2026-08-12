<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Minimal server-side input validation for Phase 2 auth endpoints.
 * No client-supplied value (role, user_id, status, etc.) is ever trusted --
 * this class only validates the shape of user-submitted register/login
 * fields (docs/ctf5.txt §56, ctf9.txt §5).
 */
final class Validator
{
    /** @return string[] list of error messages, empty if valid */
    public static function username(mixed $value): array
    {
        $errors = [];

        if (!is_string($value) || $value === '') {
            return ['Username is required.'];
        }

        if (strlen($value) < 3 || strlen($value) > 32) {
            $errors[] = 'Username must be between 3 and 32 characters.';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            $errors[] = 'Username may only contain letters, numbers, and underscores.';
        }

        return $errors;
    }

    /** @return string[] */
    public static function email(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return ['Email is required.'];
        }

        if (strlen($value) > 255 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return ['A valid email address is required.'];
        }

        return [];
    }

    /**
     * Password strength check. Deliberately simple for Phase 2: length +
     * a mix of character classes, not a specific banned-word list (that
     * belongs to a dedicated hardening pass, not core auth).
     *
     * @return string[]
     */
    public static function password(mixed $value): array
    {
        $errors = [];

        if (!is_string($value) || $value === '') {
            return ['Password is required.'];
        }

        if (strlen($value) < 10) {
            $errors[] = 'Password must be at least 10 characters long.';
        }

        if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value)) {
            $errors[] = 'Password must contain at least one letter and one number.';
        }

        return $errors;
    }

    /** @return string[] */
    public static function fullName(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_string($value) || strlen($value) > 150) {
            return ['Full name must be 150 characters or fewer.'];
        }

        return [];
    }
}
