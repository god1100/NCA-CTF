<?php

header('Content-Type: application/json');

$raw = file_get_contents('php://input');

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
    'raw_body' => $raw,
    'json' => json_decode($raw, true),
    'json_error' => json_last_error_msg(),
], JSON_PRETTY_PRINT);
