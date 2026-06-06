<?php

if (!function_exists('ensure_session_started')) {
    function ensure_session_started(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(string $key = 'default'): string
    {
        ensure_session_started();

        if (empty($_SESSION['_csrf'][$key])) {
            $_SESSION['_csrf'][$key] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'][$key];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(string $key = 'default'): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token($key), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(string $token, string $key = 'default'): bool
    {
        ensure_session_started();
        $sessionToken = $_SESSION['_csrf'][$key] ?? '';

        return is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }
}

if (!function_exists('require_valid_csrf')) {
    function require_valid_csrf(string $key = 'default'): void
    {
        $token = $_POST['_csrf_token'] ?? '';

        if (!is_string($token) || !verify_csrf_token($token, $key)) {
            http_response_code(419);
            exit('Security token expired. Please go back and try again.');
        }
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('normalize_text')) {
    function normalize_text(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}

if (!function_exists('validate_required_fields')) {
    function validate_required_fields(array $data, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field => $label) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = $label . ' is required.';
            }
        }

        return $errors;
    }
}

if (!function_exists('validate_email_address')) {
    function validate_email_address(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validate_phone_number')) {
    function validate_phone_number(string $phone): bool
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($normalized) >= 10 && strlen($normalized) <= 15;
    }
}

if (!function_exists('validate_password_strength')) {
    function validate_password_strength(string $password): bool
    {
        return strlen($password) >= 8;
    }
}

if (!function_exists('safe_redirect')) {
    function safe_redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
