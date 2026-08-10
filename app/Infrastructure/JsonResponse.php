<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Consistent JSON response helper matching the response envelope
 * defined in docs/ctf5.txt §4.
 */
final class JsonResponse
{
    public static function success(array $data = [], string $message = 'Operation successful', int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], JSON_PRETTY_PRINT);
    }

    public static function error(string $code, string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], JSON_PRETTY_PRINT);
    }
}
