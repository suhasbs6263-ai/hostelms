<?php

require_once(__DIR__ . '/../config/app.php');

if (!function_exists('store_uploaded_file')) {
    function store_uploaded_file(array $file, string $targetDirectory, array $allowedExtensions, int $maxBytes = 5242880): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file uploaded.', 'path' => ''];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'File upload failed.', 'path' => ''];
        }

        if (($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'error' => 'File is too large.', 'path' => ''];
        }

        $originalName = $file['name'] ?? 'upload';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            return ['ok' => false, 'error' => 'Invalid file type.', 'path' => ''];
        }

        if (!is_dir($targetDirectory) && !@mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            return ['ok' => false, 'error' => 'Upload directory is not writable.', 'path' => ''];
        }

        if (!is_writable($targetDirectory)) {
            return ['ok' => false, 'error' => 'Upload directory is not writable.', 'path' => ''];
        }

        $fileName = uniqid('upload_', true) . '.' . $extension;
        $destination = rtrim($targetDirectory, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        if (!@move_uploaded_file($file['tmp_name'], $destination)) {
            return ['ok' => false, 'error' => 'Unable to save the uploaded file.', 'path' => ''];
        }

        $relativePath = 'uploads/' . basename($targetDirectory) . '/' . $fileName;

        return ['ok' => true, 'error' => '', 'path' => $relativePath];
    }
}
