<?php
session_start();

require_once __DIR__ . '/koneksi.php';

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(array $roles)
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    $user = current_user();
    if (!in_array($user['role_key'] ?? '', $roles, true)) {
        http_response_code(403);
        echo 'Akses ditolak.';
        exit;
    }
}
