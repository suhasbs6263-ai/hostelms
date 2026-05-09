<?php

if (!function_exists('redirect_to')) {
    function redirect_to(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}

if (!function_exists('check_login')) {
    function check_login(string $role = 'student'): void
    {
        if ($role === 'admin') {
            if (empty($_SESSION['admin'])) {
                redirect_to('index.php');
            }

            return;
        }

        if (empty($_SESSION['login'])) {
            redirect_to('../index.php');
        }
    }
}
