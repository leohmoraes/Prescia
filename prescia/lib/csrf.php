<?php

declare(strict_types=1);

function presciaCsrfToken(): string
{
    if (empty($_SESSION['prescia_csrf_token']) || !is_string($_SESSION['prescia_csrf_token'])) {
        $_SESSION['prescia_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['prescia_csrf_token'];
}

function presciaValidateCsrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = $_SESSION['prescia_csrf_token'] ?? '';
    if (!is_string($submitted) || !is_string($expected) || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('CSRF validation failed');
    }
}

function presciaInjectCsrfFields(string $html): string
{
    $token = htmlspecialchars(presciaCsrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';
    return (string) preg_replace('/(<form\b[^>]*>)/i', '$1' . $field, $html);
}
