<?php

define('APP_NAME', 'Hostel Management System');
define('APP_ENV', 'local');
define('APP_TIMEZONE', 'Asia/Kolkata');
define('SESSION_TIMEOUT_SECONDS', 60 * 60 * 2);

define('UPLOAD_BASE_DIR', __DIR__ . '/../uploads');
define('PROFILE_UPLOAD_DIR', UPLOAD_BASE_DIR . '/profiles');
define('DOCUMENT_UPLOAD_DIR', UPLOAD_BASE_DIR . '/documents');
define('PAYMENT_UPLOAD_DIR', UPLOAD_BASE_DIR . '/payments');

date_default_timezone_set(APP_TIMEZONE);

function app_upload_web_path(string $relativePath): string
{
    return '../uploads/' . ltrim(str_replace('\\', '/', $relativePath), '/');
}

function ensure_upload_directories_exist(): void
{
    $directories = [
        UPLOAD_BASE_DIR,
        PROFILE_UPLOAD_DIR,
        DOCUMENT_UPLOAD_DIR,
        PAYMENT_UPLOAD_DIR,
    ];

    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }
    }
}

ensure_upload_directories_exist();
